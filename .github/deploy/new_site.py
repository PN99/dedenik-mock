"""Deploy the production branch's theme and MU plugin changes to the public /new preview."""
import argparse
from dataclasses import dataclass
import ftplib
import io
import os
from pathlib import PurePosixPath
import re
import ssl
import subprocess
import sys
import uuid


ROOT = "/public_html/www/new"
MARKER = ".dedenik-deployed-revision"
BOOTSTRAP = "1c5db193c3422d054aa02dcb86b172d979314fd2"
SOURCES = ("wp-content/themes/dedenik/", "wp-content/mu-plugins/")
RUNTIME = {".php", ".css", ".js", ".json", ".png", ".jpg", ".jpeg", ".webp", ".gif", ".svg", ".woff", ".woff2", ".mo"}


class DeployError(Exception):
    pass


def allowed(path):
    if not path.startswith(SOURCES):
        return False
    parts = path.split("/")
    if any(not re.fullmatch(r"[A-Za-z0-9_-][A-Za-z0-9_.-]*", part) for part in parts):
        raise DeployError("Unsafe path in deployment scope")
    if any(part.lower() in {"uploads", "backups", "cache"} for part in parts):
        return False
    return not parts[-1].lower().startswith("wp-config") and PurePosixPath(path).suffix.lower() in RUNTIME


def git(*args):
    return subprocess.check_output(["git", *args], stderr=subprocess.DEVNULL)


@dataclass(frozen=True)
class Change:
    path: str
    before: str | None
    after: str | None


def changes(base, head):
    records = git("diff", "--raw", "--no-abbrev", "--no-renames", "-z",
                  base, head, "--", *SOURCES).split(b"\0")
    result = []
    for metadata, name in zip(records[::2], records[1::2]):
        path = name.decode("utf-8", "surrogateescape")
        old_mode, new_mode, old, new, _ = metadata[1:].decode("ascii").split()
        if {old_mode, new_mode} - {"000000", "100644", "100755"}:
            raise DeployError("Unsupported Git file type in deployment scope")
        if not allowed(path):
            continue
        result.append(Change(path, None if old_mode == "000000" else old,
                             None if new_mode == "000000" else new))

    def order(item):
        # Publish dependencies before their callers. Remove retired files last.
        if item.after is None:
            return 4, item.path
        if item.path.startswith(SOURCES[1]):
            return 3, item.path
        if item.path.startswith(SOURCES[0] + "assets/"):
            return 0, item.path
        if item.path.startswith(SOURCES[0] + "inc/"):
            return 1, item.path
        return 2, item.path

    return sorted(result, key=order)


class Remote:
    def __init__(self, ftp):
        self.ftp = ftp
        self.root()

    def root(self):
        # MLST inspects the directory itself; PWD also checks the selected FTP namespace.
        if self.kind(ROOT) != "dir":
            raise DeployError(f"Unexpected deployment root: {ROOT}")
        self.ftp.cwd(ROOT)
        if self.ftp.pwd() != ROOT:
            raise DeployError(f"Unexpected deployment root: {ROOT}")

    def kind(self, name):
        try:
            response = self.ftp.sendcmd("MLST " + name)
        except ftplib.error_perm as error:
            if not str(error).startswith("550"):
                raise
            # A 550 can mean access denied. Only a successful parent listing can confirm absence.
            directory, _, leaf = name.rpartition("/")
            for child, facts in self.ftp.mlsd(directory, facts=["type"]):
                if child == leaf:
                    raise DeployError(f"Cannot inspect existing FTPS entry: {name}")
            return None
        for line in response.splitlines():
            facts = line.lstrip().partition(" ")[0].lower().split(";")
            for fact in facts:
                if fact.startswith("type="):
                    return fact[5:]
        raise DeployError(f"Missing FTPS type information: {name}")

    def parent(self, path, create=False):
        if path != MARKER and not allowed(path):
            raise DeployError("Remote path outside deployment scope")
        self.root()
        directory = ROOT
        for part in path.split("/")[:-1]:
            directory += "/" + part
            kind = self.kind(part)
            if kind is None:
                if not create:
                    return False
                self.ftp.mkd(part)
                kind = self.kind(part)
            if kind != "dir":
                raise DeployError(f"Expected FTPS directory: {directory}")
            self.ftp.cwd(part)
            if self.ftp.pwd() != directory:
                raise DeployError(f"Unexpected FTPS directory: {directory}")
        return True

    def read_leaf(self, name):
        kind = self.kind(name)
        if kind is None:
            return None
        if kind != "file":
            raise DeployError(f"Expected regular FTPS file: {name}")
        with io.BytesIO() as data:
            self.ftp.retrbinary("RETR " + name, data.write)
            return data.getvalue()

    def read(self, path):
        try:
            if self.parent(path):
                return self.read_leaf(PurePosixPath(path).name)
            return None
        except ftplib.all_errors as error:
            raise DeployError(f"Cannot read FTPS path: {path}") from error

    def write(self, path, data, expected):
        leaf = PurePosixPath(path).name
        temporary = f".deploy-{uuid.uuid4().hex}-{leaf}"
        uploading = False
        try:
            self.parent(path, create=True)
            if self.read_leaf(leaf) != expected or self.kind(temporary) is not None:
                raise DeployError(f"FTPS path changed before upload: {path}")
            uploading = True
            self.ftp.storbinary("STOR " + temporary, io.BytesIO(data))
            if self.read_leaf(temporary) != data:
                raise DeployError(f"Temporary upload failed verification: {path}")
            if self.read(path) != expected:
                raise DeployError(f"FTPS path changed during upload: {path}")
            self.ftp.rename(temporary, leaf)
            uploading = False
            if self.read(path) != data:
                raise DeployError(f"Published file failed verification: {path}")
        except ftplib.all_errors as error:
            raise DeployError(f"Cannot publish FTPS path: {path}") from error
        finally:
            if uploading:
                # Never use an inherited working directory for cleanup after a failed CWD.
                try:
                    if self.parent(path) and self.kind(temporary) == "file":
                        self.ftp.delete(temporary)
                except ftplib.all_errors as error:
                    raise DeployError(f"Cannot clean up temporary upload for: {path}") from error

    def remove(self, path, expected):
        if path == MARKER or expected is None:
            raise DeployError("Refusing untracked deletion")
        try:
            if self.read(path) != expected:
                raise DeployError(f"FTPS path changed before deletion: {path}")
            self.ftp.delete(PurePosixPath(path).name)
            if self.read(path) is not None:
                raise DeployError(f"Deletion failed verification: {path}")
        except ftplib.all_errors as error:
            raise DeployError(f"Cannot remove FTPS path: {path}") from error


def deploy(remote, head, apply):
    if not re.fullmatch(r"[0-9a-f]{40}", head):
        raise DeployError("Invalid target revision")
    marker = remote.read(MARKER)
    pending = re.fullmatch(rb"pending ([0-9a-f]{40}) ([0-9a-f]{40})\n?", marker or b"")
    if pending:
        base, target = (sha.decode("ascii") for sha in pending.groups())
        if target != head:
            raise DeployError(f"Deployment {base}..{target} is unfinished. Rerun the job for {target} first.")
    elif marker is None:
        base = BOOTSTRAP
    elif re.fullmatch(rb"[0-9a-f]{40}\n?", marker):
        base = marker.decode("ascii").strip()
    else:
        raise DeployError("Invalid deployed revision marker")
    if subprocess.run(["git", "merge-base", "--is-ancestor", base, head], stderr=subprocess.DEVNULL).returncode:
        raise DeployError("Deployed revision is missing or is not an ancestor of this run")
    print(f"Checking {base[:12]}..{head[:12]} in {ROOT}", flush=True)
    plan = []
    for change in changes(base, head):
        before = git("cat-file", "blob", change.before) if change.before else None
        after = git("cat-file", "blob", change.after) if change.after else None
        actual = remote.read(change.path)
        if actual != after and actual != before:
            raise DeployError(f"Remote file differs from both Git revisions: {change.path}")
        plan.append((change.path, actual, after))
        action = "unchanged" if actual == after else "delete" if after is None else "upload"
        print(f"  {action}: {change.path}", flush=True)
    if not apply:
        print("FTPS preflight complete. No files written.", flush=True)
        return
    if remote.read(MARKER) != marker:
        raise DeployError("Deployed revision changed during preflight")
    if not pending and any(actual != after for _, actual, after in plan):
        next_marker = f"pending {base} {head}\n".encode("ascii")
        remote.write(MARKER, next_marker, marker)
        marker = next_marker
    for path, actual, after in plan:
        if actual != after:
            if after is None:
                remote.remove(path, actual)
            else:
                remote.write(path, after, actual)
    for path, _, after in plan:
        if remote.read(path) != after:
            raise DeployError(f"Final verification failed: {path}")
    if pending or marker is None or base != head:
        remote.write(MARKER, (head + "\n").encode("ascii"), marker)
    print(f"Verified {len(plan)} paths; deployed revision {head}.", flush=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--dry-run", action="store_true")
    mode.add_argument("--apply", action="store_true")
    args = parser.parse_args()
    head = git("rev-parse", "HEAD").decode("ascii").strip()
    with ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=30, encoding="latin-1") as ftp:
        ftp.connect(os.environ["FTPS_HOST"], int(os.environ.get("FTPS_PORT", "21")))
        ftp.login(os.environ["FTPS_USER"], os.environ["FTPS_PASSWORD"])
        ftp.prot_p()
        deploy(Remote(ftp), head, args.apply)


if __name__ == "__main__":
    try:
        main()
    except DeployError as error:
        sys.exit(str(error))
    except (ftplib.Error, EOFError, OSError, KeyError, ValueError, subprocess.CalledProcessError) as error:
        # Raw FTPS replies may contain login or unrelated hosting details.
        sys.exit(f"Deployment failed ({type(error).__name__}); check FTPS settings and Git checkout.")
