# CDexamSave

CDexamSave is a quiz access-rule plugin for Moodle 4.0 and later. It detects when an in-progress quiz stops being the active browser tab or window, records every qualifying incident, warns the student on return, and presents authorised staff with a near-real-time report.

## Maintainer and affiliation

- Author and maintainer: Carlos Díaz Bueno
- Institutional affiliation: Colegio Sagrada Familia – Siervas de San José, Salamanca, Spain
- Support: carlosdiazbueno@gmail.com
- Source: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave
- Issues: https://github.com/cdiazbu/moodle-quizaccess_cdexamsave/issues

Copyright is held by Carlos Díaz Bueno. The institutional affiliation identifies the educational setting in which the plugin was developed and does not by itself transfer ownership or legal responsibility to the school.

Compatibility does not replace Moodle security maintenance. Moodle 4.0–4.4 are end-of-life releases; production examination sites should run a currently supported Moodle branch.

Technical component: `quizaccess_cdexamsave`  
Install location: `mod/quiz/accessrule/cdexamsave`  
Release: 1.0.1-rc1

## Release status

This package is an initial public-release candidate. Its structural validator and browser simulation pass, but publication should wait until the complete acceptance matrix in `TESTING.md` has been run on a real Moodle 4.5 staging site with developer debugging enabled. In particular, verify a real student attempt and the teacher live report; teacher preview attempts are intentionally excluded.

## Documentation

- `docs/MARKETPLACE_LISTING_EN.md`: English text ready for the public listing.
- `docs/MARKETPLACE_LISTING_ES.md`: Spanish public-listing text.
- `docs/ADMIN_TEACHER_GUIDE_EN.md`: administrator and teacher guide in English.
- `docs/GUIA_ADMIN_PROFESORADO_ES.md`: administrator and teacher guide in Spanish.
- `docs/PRIVACY_NOTICE_TEMPLATE_EN.md`: editable privacy-information template.
- `docs/PLANTILLA_INFORMACION_PRIVACIDAD_ES.md`: editable Spanish privacy-information template.
- `docs/PUBLICATION_GUIDE_ES.md`: step-by-step publication guide.
- `docs/RELEASE_CHECKLIST.md`: technical release gate and evidence checklist.
- `docs/SCREENSHOT_PLAN.md`: required screenshots and privacy precautions.

## What it provides

- Per-quiz activation in **Quiz settings > Extra restrictions on attempts > CDexamSave**.
- Detection through the standard Page Visibility, Focus and Page Lifecycle browser APIs.
- Configurable grace period to suppress very short accidental focus changes.
- A clear student acknowledgement when focus returns.
- Teacher report refreshed every 2–30 seconds, with active attempts prioritised by risk state.
- Optional native browser alerts for new incidents while the teacher keeps the report open.
- Connection heartbeats, stale-connection detection and live incident duration.
- Group-aware access that respects Moodle separate-groups rules.
- CSV export of the complete visible incident history.
- Idempotent writes, retry queue and beacon transport for page lifecycle events.
- Dedicated Moodle capabilities, scheduled retention cleanup, backup/restore of quiz settings and a complete Privacy API provider.
- Spanish and English interfaces.

## Installation

### Moodle web installer

1. Sign in as a site administrator.
2. Open **Site administration > Plugins > Install plugins**.
3. Upload the ZIP whose top-level folder is `cdexamsave`.
4. Confirm the detected component `quizaccess_cdexamsave` and complete the database upgrade.
5. Purge Moodle caches once installation is complete.
6. Confirm that Moodle cron runs normally; retention cleanup depends on it.

### Server installation

1. Copy the `cdexamsave` directory to `mod/quiz/accessrule/`.
2. Ensure the web-server account has the same ownership and permissions as other Moodle plugins.
3. Visit **Site administration > Notifications** and complete the upgrade.
4. Purge caches.

No core Moodle file is changed.

## Configuration

Global limits are under **Site administration > Plugins > Activity modules > Quiz > Quiz access rules > CDexamSave**:

- data retention: 180 days by default;
- teacher report refresh: 3 seconds;
- student heartbeat: 10 seconds;
- disconnected threshold: 35 seconds;
- maximum incidents per attempt: 2,000.

To enable a specific exam, edit the quiz and activate **Enable focus monitoring** in the CDexamSave section. Choose whether the student must acknowledge incidents and select the grace period.

The live-report link appears in the quiz access information for teachers who have `quizaccess/cdexamsave:viewreport`. The CSV button additionally requires `quizaccess/cdexamsave:exportreport`.

## How detection works

The browser emits a loss signal when the quiz tab becomes hidden, the window loses focus, the page is suspended, or the page is closed. CDexamSave merges overlapping browser signals into one incident. If the student returns before the configured grace period, the provisional incident is discarded. Otherwise the open incident is shown to the teacher and is closed when focus returns.

Server receipt time is authoritative for the event chronology. Bounded client duration is retained so that an incident can still be reconstructed when a background browser delays or loses its initial beacon.

## Important limits

CDexamSave is a monitoring and deterrence tool, not a locked browser.

- A normal web page cannot prevent Alt+Tab, open applications, virtual desktops, browser extensions, another device or an external AI service.
- It cannot determine which other tab or application was used; it only knows that Moodle stopped being active.
- Browser controls, operating-system dialogs, accessibility tools, notifications or brief connectivity problems can generate legitimate incidents.
- A technically advanced user can interfere with client-side JavaScript. Server validation prevents impersonating another user or attempt, but cannot make an unmanaged device trustworthy.
- Mobile operating systems may suspend background JavaScript, so an open incident can remain active until the student returns.
- The event history is evidence for review, not proof by itself that cheating occurred.

For high-stakes examinations, combine CDexamSave with randomised questions and answers, one-question-per-page navigation where appropriate, restricted review options, access-network controls, in-person supervision and Safe Exam Browser on supported managed devices.

## Security and privacy

- Every collector write requires the logged-in Moodle session and a valid `sesskey`.
- The server verifies attempt ownership, non-preview status, in-progress state and per-quiz activation.
- Event and page-session UUIDs make writes idempotent.
- Live and export endpoints perform Moodle login, context and capability checks.
- Separate-group restrictions are revalidated server-side.
- All report cells are built with `textContent`; browser data is never injected as HTML.
- No visited URL, destination application, IP address, clipboard content, keystroke, screenshot, microphone or camera data is collected.
- Browser alerts are opt-in because the student name and incident type may be visible in the teacher device's operating-system notification area.
- Personal data export and deletion are implemented through Moodle's Privacy API.
- A daily scheduled task enforces the configured retention period.

Before deployment, the institution should document the educational purpose and legal basis, inform students, set a proportionate retention period, limit report capabilities, and define a procedure for reviewing false positives.

## Backup and restore

Quiz backup includes only the three reusable CDexamSave settings. Monitoring sessions and personal incident history are deliberately excluded from course backups and duplication.

## Automated tests

From a Moodle development installation:

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite quizaccess_cdexamsave_testsuite
```

Rebuild AMD assets after editing JavaScript source:

```bash
npx grunt amd --root=mod/quiz/accessrule/cdexamsave
```

The distributed ZIP already contains compiled AMD files.

## Manual acceptance test

1. Create a test quiz, enable CDexamSave and start an attempt as a student.
2. Open the live report in a separate teacher account.
3. Switch the student to another tab for longer than the grace period.
4. Confirm that the teacher sees **Outside Moodle** within the configured refresh interval.
5. Return to the quiz and acknowledge the student warning.
6. Confirm that the incident closes with a duration and appears in the CSV.
7. Repeat with a sub-grace switch, normal quiz page submission, separate groups, session expiry and temporary network loss.

## Licence

GNU GPL v3 or later, matching Moodle.
