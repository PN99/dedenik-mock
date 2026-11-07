import ftplib
import subprocess
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

import new_site


REPOSITORY = Path(__file__).resolve().parents[2]
DISCUSSION_REVISION = "f5b2c8c12c6c02094103e04625a266c234aa416c"
THEME = "wp-content/themes/dedenik/"
PREVIEW_PLUGIN = "wp-content/mu-plugins/dedenik-new-nahled.php"
DISCUSSION_FILES = {
    PREVIEW_PLUGIN,
    THEME + "assets/js/comments.js",
    THEME + "comments.php",
    THEME + "inc/setup.php",
}


def git_blob(revision, path):
    return subprocess.check_output(
        ["git", "-C", str(REPOSITORY), "show", f"{revision}:{path}"]
    )


class MemoryRemote:
    """Binary FTP payloads with the same optimistic preconditions as the real server."""

    def __init__(self, files):
        self.files = dict(files)
        self.writes = []

    def read(self, path):
        return self.files.get(path)

    def write(self, path, data, expected):
        if self.read(path) != expected:
            raise AssertionError(f"Wrong update precondition for {path}")
        self.files[path] = data
        self.writes.append(path)

    def remove(self, path, expected):
        if path not in self.files or self.read(path) != expected:
            raise AssertionError(f"Wrong deletion precondition for {path}")
        del self.files[path]
        self.writes.append(path)


class FirstDiscussionDeploy(unittest.TestCase):
    def setUp(self):
        self.before = new_site.BOOTSTRAP
        self.original = {
            path: git_blob(self.before, path)
            for path in DISCUSSION_FILES
            if path != THEME + "assets/js/comments.js"
        }
        self.expected = {path: git_blob(DISCUSSION_REVISION, path) for path in DISCUSSION_FILES}
        self.remote = MemoryRemote(self.original)

    def test_first_release_maps_only_the_four_discussion_files(self):
        delta = new_site.changes(self.before, DISCUSSION_REVISION)
        self.assertCountEqual([change.path for change in delta], DISCUSSION_FILES)
        new_site.deploy(self.remote, DISCUSSION_REVISION, apply=False)
        self.assertEqual(self.remote.files, self.original)
        self.assertEqual(self.remote.writes, [])

        new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
        self.assertEqual(self.remote.writes[0], new_site.MARKER)
        self.assertCountEqual(self.remote.writes[1:-1], DISCUSSION_FILES)
        self.assertEqual(self.remote.writes[-1], new_site.MARKER)
        self.assertEqual(self.remote.read(new_site.MARKER), f"{DISCUSSION_REVISION}\n".encode())
        for path, data in self.expected.items():
            self.assertEqual(self.remote.read(path), data, path)

    def test_existing_host_edits_abort_the_whole_release(self):
        self.remote.files[THEME + "inc/setup.php"] = b"Unreleased edit on the host"
        original_host = self.remote.files.copy()
        with self.assertRaises(new_site.DeployError):
            new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
        self.assertEqual(self.remote.files, original_host)
        self.assertEqual(self.remote.writes, [])

    def test_invalid_remote_revision_does_not_bootstrap_over_it(self):
        self.remote.files[new_site.MARKER] = b"not-a-revision\n"
        with self.assertRaises(new_site.DeployError):
            new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
        self.assertEqual(self.remote.writes, [])

    def test_retry_accepts_an_already_published_file(self):
        already_applied = THEME + "assets/js/comments.js"
        self.remote.files[already_applied] = self.expected[already_applied]
        new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
        self.assertNotIn(already_applied, self.remote.writes)
        self.assertEqual(self.remote.writes[-1], new_site.MARKER)
        for path, data in self.expected.items():
            self.assertEqual(self.remote.read(path), data, path)

        self.remote.writes.clear()
        new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
        self.assertEqual(self.remote.writes, [])

    def test_interrupted_release_blocks_a_different_target_until_exact_retry(self):
        actual_write = self.remote.write

        def interrupt(path, data, expected):
            actual_write(path, data, expected)
            if path != new_site.MARKER:
                raise ConnectionError("Simulated disconnect after publishing a file")

        with patch.object(self.remote, "write", side_effect=interrupt):
            with self.assertRaises(ConnectionError):
                new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
        pending = f"pending {self.before} {DISCUSSION_REVISION}\n".encode()
        self.assertEqual(self.remote.read(new_site.MARKER), pending)
        partial = self.remote.files.copy()
        self.remote.writes.clear()
        # A later push may revert a partially deployed addition. Never diff from the old base.
        for other in (self.before, "b" * 40):
            with self.subTest(other=other):
                with self.assertRaisesRegex(new_site.DeployError, DISCUSSION_REVISION):
                    new_site.deploy(self.remote, other, apply=True)
                self.assertEqual(self.remote.files, partial)
                self.assertEqual(self.remote.writes, [])

        new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
        self.assertEqual(self.remote.read(new_site.MARKER), f"{DISCUSSION_REVISION}\n".encode())
        self.assertEqual({p: self.remote.read(p) for p in DISCUSSION_FILES}, self.expected)

    def test_delete_requires_the_tracked_bytes_and_supports_retry(self):
        path = THEME + "comments.php"
        item = next(change for change in new_site.changes(self.before, DISCUSSION_REVISION)
                    if change.path == path)
        deletion = new_site.Change(path=path, before=item.before, after=None)
        protected = "wp-content/uploads/keep-me.jpg"
        self.remote.files[protected] = b"Untracked upload"
        with patch.object(new_site, "changes", return_value=[deletion]):
            self.remote.files[path] = b"Changed directly on hosting"
            with self.assertRaises(new_site.DeployError):
                new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
            self.assertEqual(self.remote.writes, [])

            self.remote.files[path] = self.original[path]
            new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
            self.assertNotIn(path, self.remote.files)
            self.assertEqual(self.remote.writes, [new_site.MARKER, path, new_site.MARKER])
            self.assertEqual(self.remote.files[protected], b"Untracked upload")

            del self.remote.files[new_site.MARKER]
            self.remote.writes.clear()
            new_site.deploy(self.remote, DISCUSSION_REVISION, apply=True)
            self.assertEqual(self.remote.writes, [new_site.MARKER])


class DeploymentBoundaries(unittest.TestCase):
    def test_delete_rejects_a_linked_parent_and_outside_paths_before_ftp_delete(self):
        ftp = Mock()
        ftp.pwd.return_value = new_site.ROOT
        ftp.sendcmd.side_effect = {
            "MLST " + new_site.ROOT: f"250-Listing\n type=dir; {new_site.ROOT}\n250 End",
            "MLST wp-content": "250-Listing\n type=OS.unix=slink; wp-content\n250 End",
        }.__getitem__
        remote = new_site.Remote(ftp)
        for path in (THEME + "comments.php", "../wp-config.php", new_site.MARKER):
            with self.subTest(path=path), self.assertRaises(new_site.DeployError):
                remote.remove(path, expected=b"tracked contents")
        ftp.delete.assert_not_called()
        ftp.retrbinary.assert_not_called()

    def test_unreadable_existing_entry_is_not_treated_as_absent(self):
        ftp = Mock()
        ftp.pwd.return_value = new_site.ROOT
        ftp.sendcmd.side_effect = [
            f"250-Listing\n type=dir; {new_site.ROOT}\n250 End",
            ftplib.error_perm("550 Access denied"),
        ]
        ftp.mlsd.return_value = [("private.php", {"type": "file"})]
        remote = new_site.Remote(ftp)
        with self.assertRaises(new_site.DeployError):
            remote.kind("private.php")

    def test_unsafe_names_inside_theme_are_rejected(self):
        for path in (
            THEME + "../other/functions.php", THEME + "assets/../../outside.php",
            THEME + "assets/images/splash\r\nDELE stray.png", THEME + ".env",
        ):
            with self.subTest(path=path), self.assertRaises(new_site.DeployError):
                new_site.allowed(path)

    def test_out_of_scope_files_never_enter_payload(self):
        for path in (
            "wp-config.php", ".htaccess", "wp-includes/version.php",
            "wp-content/uploads/2026/picture.jpg", "wp-content/plugins/akismet/akismet.php",
            "wp-content/themes/another-theme/functions.php", THEME + "README.md",
            ".github/workflows/deploy.yml",
        ):
            with self.subTest(path=path):
                self.assertFalse(new_site.allowed(path))


if __name__ == "__main__":
    unittest.main()
