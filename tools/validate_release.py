#!/usr/bin/env python3
"""Dependency-free structural validator for a CD ExamFocus release tree."""

from __future__ import annotations

import json
import re
import sys
import xml.etree.ElementTree as ET
import zlib
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
REQUIRED = {
    "version.php",
    "rule.php",
    "settings.php",
    "db/install.xml",
    "db/access.php",
    "db/services.php",
    "lang/en/quizaccess_cdexamsave.php",
    "amd/src/monitor.js",
    "amd/build/monitor.min.js",
    "amd/src/live_report.js",
    "amd/build/live_report.min.js",
    "classes/privacy/provider.php",
    "classes/external/record_signal.php",
    "classes/external/get_live_data.php",
    "pix/icon.png",
    "README.md",
    "tools/build_release.py",
}

FORBIDDEN = {"collector.php", "live.php"}

TEXT_SUFFIXES = {".cff", ".css", ".js", ".json", ".md", ".php", ".py", ".svg", ".xml", ".yml", ".yaml"}


def validate_png(path: Path) -> str | None:
    """Return an error for a truncated or corrupt PNG, otherwise None."""
    data = path.read_bytes()
    if not data.startswith(b"\x89PNG\r\n\x1a\n"):
        return "invalid PNG signature"

    offset = 8
    seen_iend = False
    while offset < len(data):
        if offset + 12 > len(data):
            return "truncated PNG chunk header"
        length = int.from_bytes(data[offset : offset + 4], "big")
        chunkend = offset + 12 + length
        if chunkend > len(data):
            return "truncated PNG chunk data"
        chunktype = data[offset + 4 : offset + 8]
        chunkdata = data[offset + 8 : offset + 8 + length]
        expectedcrc = int.from_bytes(data[offset + 8 + length : chunkend], "big")
        actualcrc = zlib.crc32(chunktype + chunkdata) & 0xFFFFFFFF
        if actualcrc != expectedcrc:
            return f"invalid PNG CRC in {chunktype.decode('ascii', errors='replace')} chunk"
        offset = chunkend
        if chunktype == b"IEND":
            seen_iend = True
            break

    if not seen_iend:
        return "missing PNG IEND chunk"
    if offset != len(data):
        return "unexpected bytes after PNG IEND chunk"
    return None


def language_keys(path: Path) -> set[str]:
    """Extract Moodle language keys without evaluating PHP."""
    text = path.read_text(encoding="utf-8")
    return set(re.findall(r"\$string\['([^']+)'\]\s*=", text))


def main() -> int:
    """Run release checks and return a process exit code."""
    failures: list[str] = []
    all_files = [
        path
        for path in ROOT.rglob("*")
        if path.is_file() and ".git" not in path.relative_to(ROOT).parts
    ]
    relative_files = {str(path.relative_to(ROOT)) for path in all_files}

    for required in sorted(REQUIRED - relative_files):
        failures.append(f"missing required file: {required}")
    for forbidden in sorted(FORBIDDEN & relative_files):
        failures.append(f"obsolete custom AJAX endpoint is still present: {forbidden}")

    try:
        install_tree = ET.parse(ROOT / "db/install.xml")
        tables = install_tree.findall("./TABLES/TABLE")
        table_names = [table.attrib.get("NAME", "") for table in tables]
        if len(table_names) != len(set(table_names)):
            failures.append("duplicate XMLDB table name")
        for table in tables:
            table_name = table.attrib.get("NAME", "")
            if not table_name or len(table_name) > 28:
                failures.append(f"invalid XMLDB table name: {table_name}")
            fields = [field.attrib.get("NAME", "") for field in table.findall("./FIELDS/FIELD")]
            if "id" not in fields or len(fields) != len(set(fields)):
                failures.append(f"invalid or duplicate fields in table: {table_name}")
            primary = table.find("./KEYS/KEY[@TYPE='primary']")
            if primary is None or primary.attrib.get("FIELDS") != "id":
                failures.append(f"missing id primary key in table: {table_name}")
    except (ET.ParseError, OSError) as error:
        failures.append(f"invalid db/install.xml: {error}")

    for path in all_files:
        data = path.read_bytes()
        if data.startswith(b"\xef\xbb\xbf"):
            failures.append(f"UTF-8 BOM is not allowed: {path.relative_to(ROOT)}")
        if path.suffix.lower() in TEXT_SUFFIXES and b"\r\n" in data:
            failures.append(f"CRLF line endings found: {path.relative_to(ROOT)}")
        if path.name in {".DS_Store", "Thumbs.db"} or "__pycache__" in path.parts:
            failures.append(f"accidental package file: {path.relative_to(ROOT)}")
        if path.suffix.lower() == ".png":
            pngerror = validate_png(path)
            if pngerror:
                failures.append(f"corrupt PNG {path.relative_to(ROOT)}: {pngerror}")

    enkeys = language_keys(ROOT / "lang/en/quizaccess_cdexamsave.php")
    spanish = ROOT / "lang/es/quizaccess_cdexamsave.php"
    if spanish.exists():
        eskeys = language_keys(spanish)
        for key in sorted(enkeys - eskeys):
            failures.append(f"Spanish translation missing: {key}")
        for key in sorted(eskeys - enkeys):
            failures.append(f"English translation missing: {key}")

    languagepacks = sorted(
        path.name for path in (ROOT / "lang").iterdir() if path.is_dir() and path.name != "en"
    )
    if languagepacks:
        failures.append(
            "plugin ZIP must ship only lang/en; submit translations through AMOS: "
            + ", ".join(languagepacks)
        )

    referenced: set[str] = set()
    get_string_pattern = re.compile(
        r"get_string\(\s*'([^']+)'\s*,\s*'quizaccess_cdexamsave'"
    )
    for path in all_files:
        if path.suffix in {".php", ".js"} and "/build/" not in str(path):
            referenced.update(get_string_pattern.findall(path.read_text(encoding="utf-8")))
    for key in sorted(referenced - enkeys):
        failures.append(f"referenced language key missing: {key}")

    version = (ROOT / "version.php").read_text(encoding="utf-8")
    if "$plugin->component = 'quizaccess_cdexamsave';" not in version:
        failures.append("version.php component is incorrect")
    if "$plugin->requires = 2024100700;" not in version:
        failures.append("Moodle 4.5 minimum version marker is missing")
    buildmatch = re.search(r"\$plugin->version\s*=\s*(\d{10});", version)
    releasematch = re.search(r"\$plugin->release\s*=\s*'([^']+)';", version)
    if not buildmatch:
        failures.append("version.php does not contain a 10-digit build number")
    if not releasematch:
        failures.append("version.php does not contain a release name")
    if "$plugin->maturity = MATURITY_STABLE;" not in version:
        failures.append("version.php is not marked as a stable release")

    if buildmatch and releasematch:
        builddate = buildmatch.group(1)[:8]
        releasedate = f"{builddate[:4]}-{builddate[4:6]}-{builddate[6:8]}"
        releasename = releasematch.group(1)

        try:
            citation = (ROOT / "CITATION.cff").read_text(encoding="utf-8")
            citationversion = re.search(r"^version:\s*([^\s#]+)\s*$", citation, re.MULTILINE)
            citationdate = re.search(r"^date-released:\s*([^\s#]+)\s*$", citation, re.MULTILINE)
            if not citationversion or citationversion.group(1) != releasename:
                failures.append("CITATION.cff version does not match version.php release")
            if not citationdate or citationdate.group(1) != releasedate:
                failures.append("CITATION.cff release date does not match version.php build date")
        except OSError as error:
            failures.append(f"cannot read CITATION.cff: {error}")

        try:
            changes = (ROOT / "CHANGES.md").read_text(encoding="utf-8")
            if f"## {releasename} — {releasedate}" not in changes:
                failures.append("CHANGES.md latest release heading does not match version.php")
        except OSError as error:
            failures.append(f"cannot read CHANGES.md: {error}")

    for module in ("monitor", "live_report"):
        source = ROOT / f"amd/src/{module}.js"
        build = ROOT / f"amd/build/{module}.min.js"
        sourcemap = ROOT / f"amd/build/{module}.min.js.map"
        if build.exists() and b"define(" not in build.read_bytes():
            failures.append(f"compiled AMD file is not an AMD module: {module}")
        if source.exists() and sourcemap.exists():
            try:
                mapdata = json.loads(sourcemap.read_text(encoding="utf-8"))
                mappedsource = mapdata["sourcesContent"][0]
                expectedsource = source.read_text(encoding="utf-8")
                if mappedsource != expectedsource:
                    failures.append(f"compiled AMD source map does not match source: {module}")
            except (json.JSONDecodeError, KeyError, IndexError, OSError) as error:
                failures.append(f"invalid AMD source map for {module}: {error}")

    if failures:
        print("CD ExamFocus release validation failed:")
        for failure in failures:
            print(f"- {failure}")
        return 1

    print(
        f"CD ExamFocus release validation passed: {len(all_files)} files, "
        f"{len(enkeys)} language keys."
    )
    return 0


if __name__ == "__main__":
    sys.exit(main())
