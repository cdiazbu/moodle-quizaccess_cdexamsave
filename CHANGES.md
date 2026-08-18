# Change log

## 1.0.3 — 2026-08-18

- Replaced the custom student collector and live-report endpoints with Moodle AJAX external functions registered in `db/services.php` and consumed through `core/ajax`.
- Preserved beacon and keepalive delivery for page lifecycle signals while routing them through Moodle's standard external-service endpoint.
- Added configurable, neutral human-review thresholds based on incident count and cumulative time away.
- Added a review-priority summary card, student search and focused filters to the live teacher report.
- Added a new attempt-summary CSV covering all non-preview attempts, including attempts with zero incidents, alongside the detailed incident export.
- Added explicit interface guidance that review priority is a triage aid rather than proof of misconduct.
- Expanded automated coverage for review thresholds and the permission-aware live external function.
- Strengthened the public Moodle Plugin CI workflow across PHP 8.1–8.3, MariaDB and PostgreSQL on Moodle 4.5.
- Added deterministic package construction and PNG-integrity checks for release assets.

## 1.0.2 — 2026-07-31

- Introduced the public brand **CD ExamFocus** while retaining the stable `quizaccess_cdexamsave` technical component.
- Added the final professional icon, plugin artwork and Marketplace assets.
- Reworked the README and Marketplace messaging around privacy-first focus integrity and external AI-resource indicators.
- Explicitly documented that a focus record cannot guarantee that AI was not used.
- Added a bilingual brand guide and a Spanish launch and community-growth plan.
- Promoted the tested publication build to stable maturity for its first Marketplace submission.

## 1.0.1-rc1 — 2026-07-31

- Added the bilingual Marketplace publication, administration, privacy and release documentation set.
- Removed the collector's unnecessary direct fallback to the PHP `$_POST` superglobal; monitoring clients use the validated JSON request body.
- Marked the package as a beta release candidate until real Moodle 4.5 acceptance evidence is complete.

## 1.0.0 — 2026-07-31

- Initial stable release.
- Initial compatibility bridge, superseded by the Moodle 4.5 minimum used for the Marketplace release.
- Idempotent focus-loss collector with retry and beacon support.
- Student incident acknowledgement.
- Group-aware live teacher report and CSV export.
- Retention task, Privacy API, capabilities and quiz-settings backup/restore.
- English and Spanish language packs.
