# CD ExamFocus test matrix

## Automated checks included in the release

- Moodle PHPUnit tests for settings lifecycle, review thresholds and SQL integration.
- Moodle PHPUnit tests for UUID validation and untrusted reason normalisation.
- Moodle PHPUnit coverage for the permission-aware live AJAX external function and its declared return schema.
- Package validator for XML well-formedness, required plugin files, language parity, string references, AMD build parity and forbidden accidental files.
- Dependency-free PNG integrity checks and deterministic release-ZIP construction from Git-managed files.
- JavaScript syntax parsing for both source and compiled AMD modules.
- PHP syntax parsing for all plugin PHP files when PHP CLI is available.

## Required pre-production matrix

Run the following on a staging copy of the target Moodle site before enabling a live exam.

| Area | Cases | Expected result |
|---|---|---|
| Moodle | 4.5 and later maintained branches | Plugin installs, quiz form loads and monitoring initialises |
| Browser | Current Edge, Chrome, Firefox and Safari | One incident per focus-loss interval; no duplicate blur/visibility rows |
| Device | Windows, macOS, ChromeOS, Linux, iPadOS and Android | Supported browser reports focus changes; background suspension recovers on return |
| Quiz flow | Next page, previous page, flag question, autosave, summary, submit | Normal form submission does not create a persistent false incident |
| Focus | Shorter and longer than grace period | Short event discarded; long event opened and closed |
| Connectivity | Offline during loss and reconnect on return | Retry queue sends a bounded, idempotent incident |
| Access | Student, teacher, non-editing teacher, manager | Students cannot open report/export; authorised roles can |
| Groups | No groups, visible groups, separate groups | Server response contains only permitted users |
| Review workflow | Search; all/review/focus/disconnected filters; threshold boundaries | Rows and summary counts update without treating priority as a misconduct decision |
| Export | Attempt summary and detailed incidents; attempts with zero incidents | Both CSV files respect capability/group restrictions and neutralise formula prefixes |
| Lifecycle | Submit, abandon, delete attempt | Session becomes inactive; open incident closes; deleted attempt data is removed |
| Privacy | Export/delete user request; context-wide deletion | Only approved context/user data is exported or erased |
| Retention | Run cleanup task with old and new records | Only records older than configured cutoff are removed |
| Backup | Backup, restore and duplicate monitored quiz | Settings restored; personal incident data not copied |

## Load test target

For the default three-second report interval, test at least the largest expected simultaneous cohort. Watch database query time, PHP workers and browser network traffic. Increase the refresh interval to five or ten seconds if many teachers will keep several reports open concurrently.
