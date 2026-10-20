"""Package only the theme, without runtime, fixtures or production data."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import re
base = Path(__file__).resolve().parent
source = base / 'dedenik'
version = re.search(r'^Version:\s*(\S+)', (source / 'style.css').read_text(encoding='utf-8'), re.M).group(1)
output = base.parent / 'dist' / ('dedenik-' + version + '.zip')
output.parent.mkdir(exist_ok=True)
allowed = {'.php', '.css', '.js', '.json', '.png', '.webp', '.md', '.txt'}
with ZipFile(output, 'w', ZIP_DEFLATED) as archive:
    for path in sorted(source.rglob('*')):
        if path.is_file():
            if path.suffix not in allowed or path.name == 'wp-config.php':
                raise RuntimeError('Unexpected file in theme: ' + str(path))
            archive.write(path, path.relative_to(base).as_posix())
with ZipFile(output) as archive:
    assert archive.testzip() is None
    assert 'dedenik/style.css' in archive.namelist()
    assert 'dedenik/index.php' in archive.namelist()
print(output)
