# Contributing to CDexamSave

Thank you for helping improve CDexamSave.

## Before opening an issue

- Search existing issues.
- Reproduce on a supported Moodle version with developer debugging enabled.
- Test with a real student attempt; teacher preview is intentionally excluded.
- Remove names, email addresses, quiz content, session identifiers and exported monitoring data.

Include Moodle, CDexamSave, PHP, database, browser and operating-system versions; exact steps; expected and actual behaviour; and redacted logs.

## Pull requests

- Create a focused branch from the current development branch.
- Follow Moodle coding style and use Moodle APIs.
- Keep comments, identifiers and commit messages in English.
- Add or update PHPUnit and browser/simulation tests for changed behaviour.
- Update language strings, documentation and `CHANGES.md` when relevant.
- Rebuild AMD output after JavaScript changes and include source and built files.
- Run the full validation and the relevant real Moodle acceptance tests.
- Do not add external runtime dependencies, telemetry or data collection without prior design and privacy review.

By contributing, you confirm that you have the right to submit the work under GNU GPL v3 or later.
