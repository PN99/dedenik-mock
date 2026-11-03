"""Copy only absent public media from production uploads to fixed staging uploads."""
import concurrent.futures, ftplib, hashlib, json, os, pathlib, re, ssl, tempfile, threading, uuid

SOURCE='/public_html/www'
TARGET='/public_html/svetdede'
HERE=pathlib.Path(__file__).parent

def valid(path):
    return bool(re.fullmatch(r'(?:wp-content/uploads/\d{4}/\d{2}/|php_uploads/)[^/\\\x00-\x1f]+',path)) and pathlib.PurePosixPath(path).suffix.lower() in {'.jpg','.jpeg','.png','.gif','.webp','.avif','.bmp'}

def connect():
    host=os.environ['SVETDEDE_FTP_HOST']
    assert host=='dw132.nameserver.sk'
    ftp=ftplib.FTP_TLS(context=ssl.create_default_context(),timeout=90)
    ftp.connect(host,int(os.environ.get('SVETDEDE_FTP_PORT','21')))
    ftp.login(os.environ['SVETDEDE_FTP_USER'],os.environ['SVETDEDE_FTP_PASSWORD'])
    ftp.prot_p(); ftp.voidcmd('TYPE I')
    for root in (SOURCE,TARGET):
        ftp.cwd(root); assert ftp.pwd().rstrip('/')==root
    return ftp

def listing(ftp,root,folder,optional=False):
    try:
        # Historical directories contain mixed encodings. Preserve undecodable
        # names without changing the exact UTF-8 names in the reviewed manifest.
        ftp.sendcmd('OPTS MLST type;size;')
        raw=bytearray()
        def receive(block):
            raw.extend(block); assert len(raw)<=32*1024*1024
        ftp.retrbinary('MLSD '+root+'/'+folder,receive,blocksize=65536)
        result={}
        for line in bytes(raw).splitlines():
            facts,sep,name=line.partition(b' ')
            if not sep: continue
            values=dict(v.split(b'=',1) for v in facts.split(b';') if b'=' in v)
            if values.get(b'type')==b'file': result[name.decode('utf-8','surrogateescape')]=int(values.get(b'size',b'0'))
        return result
    except ftplib.error_perm as e:
        if optional and str(e).startswith('550'): return {}
        raise

def inventory(ftp,files):
    result={'existing':[],'missing':[],'unavailable_source':[],'bytes_to_copy':0}
    for folder in sorted({str(pathlib.PurePosixPath(p).parent) for p in files}):
        source=listing(ftp,SOURCE,folder,True); target=listing(ftp,TARGET,folder,True)
        for p in [p for p in files if str(pathlib.PurePosixPath(p).parent)==folder]:
            name=pathlib.PurePosixPath(p).name
            if name in target:
                result['existing'].append(p)
            elif name in source and source[name]>0:
                result['missing'].append({'path':p,'bytes':source[name]}); result['bytes_to_copy']+=source[name]
            else: result['unavailable_source'].append(p)
    return result

def ensure_folder(ftp,folder):
    assert re.fullmatch(r'wp-content/uploads/\d{4}/\d{2}|php_uploads',folder)
    current=TARGET
    for part in pathlib.PurePosixPath(folder).parts:
        current+='/'+part
        try: ftp.cwd(current)
        except ftplib.error_perm as e:
            if not str(e).startswith('550'): raise
            ftp.mkd(current); ftp.cwd(current)

def exists(ftp,path):
    assert path.startswith(TARGET+'/')
    ftp.voidcmd('TYPE I')
    try: return ftp.size(path) is not None
    except ftplib.error_perm as e:
        if str(e).startswith('550'): return False
        raise

def main():
    manifest=json.loads((HERE/'uploads-manifest.json').read_text(encoding='utf-8'))
    assert manifest['source_root']==SOURCE and manifest['target_root']==TARGET
    files=manifest['files']; assert len(files)==len(set(files)) and all(valid(p) for p in files)
    apply=os.environ.get('UPLOAD_APPLY')=='true'
    with connect() as ftp:
        before=inventory(ftp,files)
        report={'apply':apply,'before':before,'copied':[],'raced_existing':[]}
        print(json.dumps({'apply':apply,'requested':len(files),'already_present':len(before['existing']),'missing':len(before['missing']),'source_unavailable':len(before['unavailable_source']),'bytes_to_copy':before['bytes_to_copy']}),flush=True)
        (HERE/'uploads-report.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
        if not apply:
            print('Unavailable source:',json.dumps(before['unavailable_source'],ensure_ascii=False),flush=True)
            return
        # Missing source entries require a reviewed manifest; never silently claim completion.
        assert not before['unavailable_source'],'Some requested source files are absent'
        for folder in sorted({str(pathlib.PurePosixPath(v['path']).parent) for v in before['missing']}):
            ensure_folder(ftp,folder)
    report_lock=threading.Lock()
    def record(kind,value):
        with report_lock:
            report[kind].append(value)
            (HERE/'uploads-report.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
            done=len(report['copied'])+len(report['raced_existing'])
            if done%10==0 or done==len(before['missing']): print('Verified media copied:',done,'/',len(before['missing']),flush=True)
    def copy_one(ftp,item):
            path=item['path']; folder=str(pathlib.PurePosixPath(path).parent); name=pathlib.PurePosixPath(path).name
            if exists(ftp,TARGET+'/'+path): record('raced_existing',path); return
            temp=TARGET+'/'+path+'.uploading-'+uuid.uuid4().hex
            assert temp.startswith(TARGET+'/') and valid(path)
            uploaded=False
            try:
                with tempfile.SpooledTemporaryFile(max_size=8*1024*1024) as stream:
                    digest=hashlib.sha256(); size=0
                    def receive(block):
                        nonlocal size
                        size+=len(block); assert size<=128*1024*1024
                        digest.update(block); stream.write(block)
                    ftp.retrbinary('RETR '+SOURCE+'/'+path,receive,blocksize=65536)
                    assert size==item['bytes'] and size>0
                    stream.seek(0); uploaded=True
                    ftp.storbinary('STOR '+temp,stream,blocksize=65536)
                    verify=hashlib.sha256(); ftp.retrbinary('RETR '+temp,verify.update,blocksize=65536)
                    assert verify.digest()==digest.digest(),'Uploaded file checksum mismatch'
                    if exists(ftp,TARGET+'/'+path):
                        record('raced_existing',path)
                    else:
                        ftp.rename(temp,TARGET+'/'+path); uploaded=False
                        record('copied',{'path':path,'bytes':size,'sha256':digest.hexdigest()})
            finally:
                if uploaded: ftp.delete(temp)
    def worker(items):
        if not items: return
        with connect() as ftp:
            for item in items: copy_one(ftp,item)
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
        futures=[pool.submit(worker,before['missing'][n::3]) for n in range(3)]
        for future in futures: future.result()
    with connect() as ftp:
        report['after']=inventory(ftp,files)
        (HERE/'uploads-report.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
        assert not report['after']['missing'] and not report['after']['unavailable_source']
        print('Verified all requested media present:',len(files),flush=True)

if __name__=='__main__':
    try: main()
    except Exception as e:
        print('Uploads sync failed:',type(e).__name__,flush=True)
        raise SystemExit(1)
