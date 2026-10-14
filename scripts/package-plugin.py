#!/usr/bin/env python3
"""Build an installable plugin ZIP from tracked runtime files only."""

import argparse
import hashlib
from pathlib import Path
import subprocess
import zipfile


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    root = Path(__file__).resolve().parent.parent
    runtime_dirs = {"amd", "archive_templates", "classes", "db", "lang", "style", "templates", "docs"}
    runtime_files = {"README.md", "README.fr.md", "SECURITY.md", "index.php", "lib.php", "locallib.php", "archive_process.php",
                     "settings.php", "serve_temp.php", "download.php", "styles.css", "version.php"}
    tracked = subprocess.check_output(["git", "ls-files", "-z"], cwd=root).decode().split("\0")
    files = sorted(name for name in tracked if name and
                   (Path(name).parts[0] in runtime_dirs or name in runtime_files))
    for name in files:
        path = root / name
        if path.is_symlink() or not path.is_file() or not path.resolve().is_relative_to(root):
            raise RuntimeError(f"Invalid release file: {name}")
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(args.output, "w", zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
        for name in files:
            archive.write(root / name, "flatarchive/" + name)
    with zipfile.ZipFile(args.output) as archive:
        if archive.testzip() is not None:
            raise RuntimeError("ZIP integrity check failed")
        names = set(archive.namelist())
        required = {"version.php", "archive_process.php", "settings.php", "serve_temp.php", "download.php", "db/upgrade.php",
                    "classes/external/get_tree.php", "amd/build/selectors.min.js"}
        if not {"flatarchive/" + name for name in required}.issubset(names):
            raise RuntimeError("Required plugin files missing")
        if any("/vendor/" in name or "/ajax/" in name or name.endswith("/composer.json") for name in names):
            raise RuntimeError("Unexpected obsolete files in release")
    print(args.output.resolve())
    print(f"ZIP integrity: OK; {args.output.stat().st_size:,} bytes; {len(files)} files")
    print("SHA-256:", hashlib.sha256(args.output.read_bytes()).hexdigest())


if __name__ == "__main__":
    main()
