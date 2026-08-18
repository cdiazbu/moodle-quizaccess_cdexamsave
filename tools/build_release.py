#!/usr/bin/env python3
"""Build a deterministic CD ExamFocus Marketplace ZIP from Git files."""

from __future__ import annotations

import argparse
import hashlib
import re
import subprocess
import sys
import zipfile
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TOP_LEVEL = "cdexamsave"
EXCLUDED_PREFIXES = (".github/",)


def plugin_metadata() -> tuple[str, tuple[int, int, int, int, int, int]]:
    """Return the release label and a ZIP-safe date derived from version.php."""
    versionfile = (ROOT / "version.php").read_text(encoding="utf-8")
    release = re.search(r"\$plugin->release\s*=\s*'([^']+)'", versionfile)
    build = re.search(r"\$plugin->version\s*=\s*(\d{10})", versionfile)
    if not release or not build:
        raise ValueError("version.php does not contain a valid release and build number")

    builddate = build.group(1)[:8]
    year, month, day = int(builddate[:4]), int(builddate[4:6]), int(builddate[6:8])
    return release.group(1), (year, month, day, 0, 0, 0)


def release_files() -> list[Path]:
    """Return tracked or staged files, excluding repository-only metadata."""
    result = subprocess.run(
        ["git", "ls-files"],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
    )
    paths = []
    for line in result.stdout.splitlines():
        if not line or line.startswith(EXCLUDED_PREFIXES):
            continue
        path = ROOT / line
        if path.is_file():
            paths.append(path)
    return sorted(paths, key=lambda item: item.relative_to(ROOT).as_posix())


def build(output: Path) -> tuple[int, str]:
    """Create the ZIP and return its file count and SHA-256 digest."""
    release, zipdate = plugin_metadata()
    files = release_files()
    if not files:
        raise ValueError("no release files were found")

    output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in files:
            relative = path.relative_to(ROOT).as_posix()
            info = zipfile.ZipInfo(f"{TOP_LEVEL}/{relative}", date_time=zipdate)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = (path.stat().st_mode & 0xFFFF) << 16
            archive.writestr(info, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)

    digest = hashlib.sha256(output.read_bytes()).hexdigest()
    expected = f"CD-ExamFocus-{release}.zip"
    if output.name != expected:
        print(f"Note: canonical release filename is {expected}.", file=sys.stderr)
    return len(files), digest


def main() -> int:
    """Parse arguments, create the release archive and report its digest."""
    release, _ = plugin_metadata()
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--output",
        type=Path,
        default=ROOT.parent / f"CD-ExamFocus-{release}.zip",
        help="Destination ZIP path",
    )
    args = parser.parse_args()

    try:
        count, digest = build(args.output.resolve())
    except (OSError, subprocess.CalledProcessError, ValueError) as error:
        print(f"Release build failed: {error}", file=sys.stderr)
        return 1

    print(f"Built {args.output.resolve()} with {count} files")
    print(f"SHA-256: {digest}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
