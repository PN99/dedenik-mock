"""Fill absent staging media from the original FTP; preserve exact Unicode paths."""
import concurrent.futures
import ftplib
import hashlib
import json
import os
import pathlib
import re
import ssl
import tempfile
import threading
import uuid

SOURCE = '/public_html/www/wp-content/uploads'
TARGET = '/public_html/svetdede/wp-content/uploads'
HERE = pathlib.Path(__file__).resolve().parent
MAX_BYTES = 128 * 1024 * 1024
WORKERS = 5
EXTENSIONS = {'.jpg', '.jpeg', '.png', '.gif', '.webp', '.avif', '.bmp', '.heic',
              '.mp3', '.mp4', '.mov', '.avi', '.pdf', '.docx', '.ogg', '.wav'}


def valid(path):
    return (isinstance(path, str)
            and re.fullmatch(r'[0-9]{4}/(?:0[1-9]|1[0-2])/[^/\\\x00-\x1f\x7f]+', path) is not None
            and pathlib.PurePosixPath(path).suffix.lower() in EXTENSIONS)


def load_manifest():
    manifest = json.loads((HERE / 'uploads-manifest.json').read_text(encoding='utf-8'))
    if manifest['source_root'] != SOURCE or manifest['target_root'] != TARGET:
        raise ValueError('Unapproved source or target root')
    files = manifest['files']
    if not isinstance(files, list) or not files:
        raise ValueError('Manifest must contain files')
    seen = set()
    for item in files:
        path, size = item['path'], item['bytes']
        if not valid(path) or path in seen or type(size) is not int or not 0 < size <= MAX_BYTES:
            raise ValueError('Invalid or duplicate media entry')
        if 'sha256' in item and re.fullmatch('[0-9a-f]{64}', item['sha256']) is None:
            raise ValueError('Invalid local checksum')
        seen.add(path)
    return files


def connect():
    host = os.environ['SVETDEDE_FTP_HOST']
    if host != 'dw132.nameserver.sk':
        raise ValueError('Unapproved FTP host')
    ftp = ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=90, encoding='utf-8')
    ftp.connect(host, int(os.environ.get('SVETDEDE_FTP_PORT', '21')))
    ftp.login(os.environ['SVETDEDE_FTP_USER'], os.environ['SVETDEDE_FTP_PASSWORD'])
    ftp.prot_p()
    ftp.voidcmd('TYPE I')
    for root in (SOURCE, TARGET):
        ftp.cwd(root)
        if ftp.pwd().rstrip('/') != root:
            ftp.close()
            raise ValueError('FTP root did not resolve exactly')
    return ftp


def listing(ftp, root, folder, optional=False):
    if root not in (SOURCE, TARGET) or re.fullmatch(r'[0-9]{4}/(?:0[1-9]|1[0-2])', folder) is None:
        raise ValueError('Unapproved listing path')
    try:
        ftp.sendcmd('OPTS MLST type;size;')
        raw = bytearray()

        def receive(block):
            raw.extend(block)
            if len(raw) > 32 * 1024 * 1024:
                raise ValueError('Unexpectedly large directory listing')

        # Some historical directories contain non-UTF-8 names. Decode only after
        # parsing bytes, retaining exact manifest names and unrelated bad bytes.
        ftp.retrbinary('MLSD ' + root + '/' + folder, receive, blocksize=65536)
        result = {}
        for line in bytes(raw).splitlines():
            facts, separator, name = line.partition(b' ')
            if not separator:
                continue
            values = dict(value.split(b'=', 1) for value in facts.split(b';') if b'=' in value)
            kind = values.get(b'type', b'').decode('ascii', 'replace')
            if kind in ('cdir', 'pdir'):
                continue
            result[name.decode('utf-8', 'surrogateescape')] = {
                'bytes': int(values.get(b'size', b'0')), 'type': kind}
        return result
    except ftplib.error_perm as error:
        if optional and str(error).startswith('550'):
            return {}
        raise


def inventory(ftp, files):
    result = {'existing': [], 'missing': [], 'unavailable_source': [],
              'source_size_mismatches': [], 'target_size_mismatches': [],
              'target_non_files': [], 'bytes_to_copy': 0}
    folders = {}
    for item in files:
        folders.setdefault(str(pathlib.PurePosixPath(item['path']).parent), []).append(item)
    for folder, items in sorted(folders.items()):
        source = listing(ftp, SOURCE, folder, True)
        target = listing(ftp, TARGET, folder, True)
        for item in items:
            path, size = item['path'], item['bytes']
            name = pathlib.PurePosixPath(path).name
            if name in target:
                result['existing'].append(path)
                if target[name]['type'] != 'file':
                    result['target_non_files'].append(path)
                elif target[name]['bytes'] != size:
                    result['target_size_mismatches'].append({
                        'path': path, 'local_bytes': size, 'target_bytes': target[name]['bytes']})
            elif name not in source or source[name]['type'] != 'file' or source[name]['bytes'] <= 0:
                result['unavailable_source'].append(path)
            elif source[name]['bytes'] != size:
                result['source_size_mismatches'].append({
                    'path': path, 'local_bytes': size, 'source_bytes': source[name]['bytes']})
            else:
                result['missing'].append(dict(item))
                result['bytes_to_copy'] += size
    return result


def ensure_folder(ftp, folder):
    if re.fullmatch(r'[0-9]{4}/(?:0[1-9]|1[0-2])', folder) is None:
        raise ValueError('Unapproved destination folder')
    current = TARGET
    for part in folder.split('/'):
        current += '/' + part
        try:
            ftp.cwd(current)
        except ftplib.error_perm as error:
            if not str(error).startswith('550'):
                raise
            ftp.mkd(current)
            ftp.cwd(current)
        if ftp.pwd().rstrip('/') != current:
            raise ValueError('Destination folder did not resolve exactly')


def exists(ftp, path):
    if not valid(path):
        raise ValueError('Unapproved target path')
    try:
        response = ftp.sendcmd('MLST ' + TARGET + '/' + path)
        if not response.startswith('250'):
            raise ValueError('Unexpected MLST response')
        return True
    except ftplib.error_perm as error:
        if str(error).startswith('550'):
            return False
        if str(error).startswith(('500', '502', '504')):
            folder, name = path.rsplit('/', 1)
            return name in listing(ftp, TARGET, folder, True)
        raise


def delete_own_temp(ftp, path, temp):
    prefix = TARGET + '/' + path + '.uploading-'
    if not valid(path) or not temp.startswith(prefix) or re.fullmatch('[0-9a-f]{32}', temp[len(prefix):]) is None:
        raise ValueError('Unapproved temporary file cleanup')
    try:
        ftp.delete(temp)
    except ftplib.error_perm as error:
        if not str(error).startswith('550'):
            raise


def copy_one(ftp, item):
    path = item['path']
    if not valid(path):
        raise ValueError('Unapproved media path')
    if exists(ftp, path):
        return 'raced_existing', path
    temp = TARGET + '/' + path + '.uploading-' + uuid.uuid4().hex
    uploaded = False
    try:
        with tempfile.SpooledTemporaryFile(max_size=8 * 1024 * 1024) as stream:
            digest = hashlib.sha256()
            size = 0

            def receive(block):
                nonlocal size
                size += len(block)
                if size > item['bytes'] or size > MAX_BYTES:
                    raise ValueError('Source grew beyond its reviewed size')
                digest.update(block)
                stream.write(block)

            ftp.retrbinary('RETR ' + SOURCE + '/' + path, receive, blocksize=65536)
            if size != item['bytes'] or size <= 0:
                raise ValueError('Source size changed')
            if item.get('sha256') and item['sha256'] != digest.hexdigest():
                raise ValueError('Source differs from local checksum')
            stream.seek(0)
            uploaded = True
            ftp.storbinary('STOR ' + temp, stream, blocksize=65536)
            verify = hashlib.sha256()
            verified_size = 0

            def verify_block(block):
                nonlocal verified_size
                verified_size += len(block)
                if verified_size > size:
                    raise ValueError('Uploaded file grew unexpectedly')
                verify.update(block)

            ftp.retrbinary('RETR ' + temp, verify_block, blocksize=65536)
            if verified_size != size or verify.digest() != digest.digest():
                raise ValueError('Uploaded file checksum mismatch')
            if exists(ftp, path):
                return 'raced_existing', path
            # FTP has no portable atomic rename-if-absent command. Recheck above
            # immediately before RNTO; the workflow must serialize these uploads.
            ftp.rename(temp, TARGET + '/' + path)
            uploaded = False
            return 'copied', {'path': path, 'bytes': size, 'sha256': digest.hexdigest()}
    finally:
        if uploaded:
            delete_own_temp(ftp, path, temp)


def save_report(report):
    destination = HERE / 'uploads-report.json'
    temporary = HERE / 'uploads-report.json.tmp'
    temporary.write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    temporary.replace(destination)


def main():
    files = load_manifest()
    apply = os.environ.get('UPLOAD_APPLY') == 'true'
    with connect() as ftp:
        before = inventory(ftp, files)
        report = {'apply': apply, 'requested': len(files), 'before': before,
                  'copied': [], 'raced_existing': [], 'failures': []}
        save_report(report)
        print(json.dumps({'apply': apply, 'requested': len(files),
                          'already_present': len(before['existing']), 'missing': len(before['missing']),
                          'source_unavailable': len(before['unavailable_source']),
                          'source_size_mismatches': len(before['source_size_mismatches']),
                          'target_size_mismatches': len(before['target_size_mismatches']),
                          'target_non_files': len(before['target_non_files']),
                          'bytes_to_copy': before['bytes_to_copy']}), flush=True)
        if not apply:
            return
        if before['unavailable_source'] or before['source_size_mismatches'] or before['target_non_files']:
            raise ValueError('Inventory requires review before any writes')
        for folder in sorted({str(pathlib.PurePosixPath(item['path']).parent) for item in before['missing']}):
            ensure_folder(ftp, folder)

    lock = threading.Lock()

    def record(kind, value):
        with lock:
            report[kind].append(value)
            save_report(report)
            done = len(report['copied']) + len(report['raced_existing'])
            if kind != 'failures' and (done % 25 == 0 or done == len(before['missing'])):
                print('Verified media copied:', done, '/', len(before['missing']), flush=True)

    def worker(items):
        if not items:
            return
        ftp = None
        try:
            for item in items:
                for attempt in range(1, 4):
                    try:
                        if ftp is None:
                            ftp = connect()
                        kind, value = copy_one(ftp, item)
                        record(kind, value)
                        break
                    except ftplib.all_errors as error:
                        if ftp is not None:
                            ftp.close()
                            ftp = None
                        if attempt == 3:
                            record('failures', {'path': item['path'], 'error': type(error).__name__})
                            raise
                        print('Retry media transfer:', attempt, flush=True)
                    except Exception as error:
                        record('failures', {'path': item['path'], 'error': type(error).__name__})
                        raise
        finally:
            if ftp is not None:
                ftp.close()

    with concurrent.futures.ThreadPoolExecutor(max_workers=WORKERS) as pool:
        futures = [pool.submit(worker, before['missing'][index::WORKERS]) for index in range(WORKERS)]
        for future in futures:
            future.result()
    with connect() as ftp:
        report['after'] = inventory(ftp, files)
    save_report(report)
    if any(report['after'][key] for key in ('missing', 'unavailable_source', 'source_size_mismatches', 'target_non_files')):
        raise ValueError('Some requested media remain absent')
    print('Verified all requested media present:', len(files), flush=True)


if __name__ == '__main__':
    try:
        main()
    except Exception as error:
        print('Uploads sync failed:', type(error).__name__, flush=True)
        raise SystemExit(1)
