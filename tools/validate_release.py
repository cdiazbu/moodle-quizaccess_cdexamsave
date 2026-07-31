#!/usr/bin/env python3
"""Dependency-free structural validator for a CD ExamFocus release tree."""

from __future__ import annotations

import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
REQUIRED = {
    "version.php",
    "rule.php",
    "settings.php",
    "db/install.xml",
    "db/access.php",
    "lang/en/quizaccess_cdexamsave.php",
    "amd/src/monitor.js",
    "amd/build/monitor.min.js",
    "amd/src/live_report.js",
    "amd/build/live_report.min.js",
    "classes/privacy/provider.php",
    "pix/icon.png",
    "README.md",
}

TEXT_SUFFIXES = {".cff", ".css", ".js", ".json", ".md", ".php", ".py", ".svg", ".xml", ".yml", ".yaml"}


def language_keys(path: Path) -> set[str]:
    """Extract Moodle language keys without evaluating PHP."""
    text = path.read_text(encoding="utf-8")
    return set(re.findall(r"\$string\['([^']+)'\]\s*=", text))


def main() -> int:
    """Run release checks and return a process exit code."""
    failures: list[str] = []
    all_files = [path for path in ROOT.rglob("*") if path.is_file()]
    relative_files = {str(path.relative_to(ROOT)) for path in all_files}

    for required in sorted(REQUIRED - relative_files):
        failures.append(f"missing required file: {required}")

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

    enkeys = language_keys(ROOT / "lang/en/quizaccess_cdexamsave.php")
    spanish = ROOT / "lang/es/quizaccess_cdexamsave.php"
    if spanish.exists():
        eskeys = language_keys(spanish)
        for key in sorted(enkeys - eskeys):
            failures.append(f"Spanish translation missing: {key}")
        for key in sorted(eskeys - enkeys):
            failures.append(f"English translation missing: {key}")

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

    for module in ("monitor", "live_report"):
        source = ROOT / f"amd/src/{module}.js"
        build = ROOT / f"amd/build/{module}.min.js"
        if build.exists() and source.exists() and build.stat().st_mtime < source.stat().st_mtime:
            failures.append(f"compiled AMD file is older than its source: {module}")
        if build.exists() and b"define(" not in build.read_bytes():
            failures.append(f"compiled AMD file is not an AMD module: {module}")

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
