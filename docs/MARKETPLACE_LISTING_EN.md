# CDexamSave — Moodle Marketplace listing (English)

Publication-ready metadata for the public listing. Privacy-template placeholders are maintained separately because each deploying institution must complete them for its own context.

## Product name

CDexamSave focus monitoring

## Frankenstyle component

`quizaccess_cdexamsave`

## Short description

CDexamSave records when a student's active Moodle quiz tab or window loses focus and provides authorised staff with a near-real-time, group-aware incident report.

## Full description

CDexamSave is a quiz access-rule plugin for Moodle. When enabled for a quiz, it detects browser signals indicating that an in-progress attempt is no longer the active tab or window. It consolidates overlapping signals into a single incident, applies an optional grace period, warns the student on return, and shows authorised staff a near-real-time report.

The report displays active attempts, current focus state, connection status, incident count, accumulated time away and recent incident details. Authorised staff can export the visible incident history as CSV. Separate-group restrictions and dedicated report/export capabilities are enforced on the server.

CDexamSave is designed as a monitoring and deterrence aid. It is not a locked browser and must not be represented as one. A standard browser cannot prevent application switching, identify the destination tab or application, detect another device, or make an unmanaged device tamper-proof. Focus incidents may also result from operating-system dialogs, accessibility tools, notifications, connectivity problems or mobile background suspension. Records are indicators for human review, not automatic proof of misconduct.

No external service, subscription or API key is required. Data remains inside the Moodle site's own database. The plugin does not collect destination URLs, browsing history, clipboard contents, keystrokes, screenshots, camera data, microphone data or biometric data.

## Main features

- Per-quiz activation under **Quiz settings > Extra restrictions on attempts > CDexamSave**.
- Detection using standard browser Page Visibility, focus and lifecycle signals.
- Configurable grace period for very short focus changes.
- Optional student notice after returning to the quiz.
- Teacher report with automatic refresh and optional browser notifications.
- Current connection and focus state for in-progress attempts.
- Incident history and CSV export.
- Separate-groups support and dedicated Moodle capabilities.
- Retry and deduplication controls for intermittent network conditions.
- Configurable retention with scheduled cleanup.
- Moodle Privacy API provider.
- Quiz-setting backup and restore; personal monitoring history is deliberately excluded from backups.
- English and Spanish user interfaces in the distributed institutional package.

## Requirements

- Moodle 4.0 or later according to `version.php`.
- A current desktop or mobile browser with JavaScript enabled.
- HTTPS is strongly recommended for production use.
- Moodle cron must run for retention cleanup.
- No additional Moodle plugin or external service is required.

The first public release must not claim a Moodle version as tested until the release checklist has been completed on that exact version. The initial publication target is Moodle 4.5.

## Installation

1. Back up the Moodle site and test on a staging copy first.
2. In Moodle, open **Site administration > Plugins > Install plugins**.
3. Upload the release ZIP. Its top-level directory must be `cdexamsave`.
4. Confirm the component `quizaccess_cdexamsave` and complete the database upgrade.
5. Purge Moodle caches.
6. Review global settings and verify that cron runs normally.

For a server installation, copy the `cdexamsave` directory to `mod/quiz/accessrule/`, visit **Site administration > Notifications**, complete the upgrade and purge caches. No Moodle core file is modified.

## Configuration and use

Global performance and retention options are available under the quiz access-rule settings in Site administration. Monitoring is disabled by default and must be enabled separately in each quiz.

To monitor an exam:

1. Edit the quiz.
2. Open **Extra restrictions on attempts > CDexamSave**.
3. Enable focus monitoring, choose the grace period and decide whether students must acknowledge the return notice.
4. Save the quiz.
5. Open the quiz as an authorised teacher and select **Open CDexamSave live report**.
6. Test with a separate student account making a real attempt. Teacher preview attempts are intentionally excluded.

## Privacy and data protection

The plugin processes the student's Moodle user ID, quiz and attempt identifiers, random event/session identifiers, server/client timestamps, focus signal category, incident duration, connection/focus state and heartbeat time. The report resolves the Moodle identity for authorised staff. The controller operating the Moodle site determines the purpose, lawful basis, recipients and retention period.

Before deployment, the institution should complete a documented necessity and proportionality assessment, involve its data protection officer where applicable, inform students in clear language, restrict report/export permissions, set a proportionate retention period, define a false-positive review process and determine whether a data-protection impact assessment is required. The plugin must not be used to make solely automated disciplinary or grading decisions.

## Support and issue reporting

- Source code: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave
- Issue tracker: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave/issues
- Documentation: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave/blob/main/README.md
- Author and maintainer: Carlos Díaz Bueno
- Institutional affiliation: Colegio Sagrada Familia – Siervas de San José, Salamanca, Spain
- Contact: carlosdiazbueno@gmail.com

Copyright is held by Carlos Díaz Bueno. The institutional affiliation identifies the educational setting in which the plugin was developed and does not by itself transfer ownership or legal responsibility to the school.

When reporting a problem, include the Moodle version, PHP version, database engine, browser and operating system, exact reproduction steps, relevant developer-debugging output and whether the attempt was a real student attempt or a teacher preview. Do not publish student names, attempt contents, session identifiers or exported monitoring data.

## Licence

GNU General Public License v3 or later.

## Release notes — 1.0.1-rc1

- Initial public-release candidate; not yet a final Marketplace release.
- Focus-loss collector with grace period, retry and deduplication.
- Student return notice.
- Group-aware live teacher report and CSV export.
- Retention task, Privacy API, capabilities and quiz-setting backup/restore.
- English and Spanish institutional interfaces.
- Bilingual administration, publication and privacy documentation.

Publication status: release only after the Moodle 4.5 acceptance evidence in `docs/RELEASE_CHECKLIST.md` is complete.
