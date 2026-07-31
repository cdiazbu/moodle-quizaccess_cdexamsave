# CDexamSave public release gate

Do not publish while any required item is unchecked. Record date, tester, environment and evidence link for each completed section. Never store real student data in public evidence.

## A. Ownership and public metadata

- [x] Real copyright holder replaces “CDexamSave contributors” in every source header.
- [x] Maintainer and public support contact are confirmed.
- [ ] Public repository exists as `moodle-quizaccess_cdexamsave` with plugin files at repository root.
- [ ] Public issue tracker is enabled.
- [ ] Documentation URL works without authentication.
- [ ] GPL v3-or-later licence and intellectual-property authority are confirmed.
- [ ] No secrets, personal data, generated exports or local configuration files are present.

## B. Static and package checks

- [ ] `tools/validate_release.py` passes.
- [ ] Every PHP file passes `php -l` using a PHP version supported by the target Moodle release.
- [ ] Moodle Code Checker/PHPCS passes or each remaining warning is reviewed and documented.
- [ ] Moodle PHPDoc checks pass.
- [ ] JavaScript source passes linting and built AMD files match source.
- [ ] XMLDB editor/validation passes.
- [ ] ZIP has exactly one top-level `cdexamsave/` directory.
- [ ] ZIP installs without shell commands or external dependency managers.
- [ ] ZIP contains no development caches, editor files, operating-system files or nested ZIPs.

## C. Moodle 4.5 installation and upgrade

- [ ] Fresh installation completes with developer debugging enabled and no warning/notice/error.
- [ ] Database tables, indexes, keys, capabilities, settings and scheduled task are created.
- [ ] Caches can be purged and pages load normally.
- [ ] Upgrade from the previously distributed build is tested, or the release is clearly identified as the first public build.
- [ ] Standard uninstall completes and the expected plugin data is removed.

Environment: `[Moodle build]` / `[PHP]` / `[DB and version]` / `[OS]` / `[date]` / `[tester]`.

## D. Real functional acceptance

- [ ] Monitoring can be enabled and disabled per quiz.
- [ ] Settings persist after saving and reopening the form.
- [ ] Teacher preview is excluded as documented.
- [ ] A real student attempt initialises the monitor and appears connected.
- [ ] Longer-than-grace tab change opens exactly one incident.
- [ ] Return closes the same incident and shows the student notice when enabled.
- [ ] Shorter-than-grace change leaves no persistent incident.
- [ ] Next/previous question, autosave, summary and final submission do not create persistent false incidents.
- [ ] Temporary loss of connectivity retries without duplicate incidents.
- [ ] Page close/suspension behaviour is documented and consistent with the browser.
- [ ] Maximum-incident protection is enforced.

## E. Teacher report and authorisation

- [ ] Report link is visible to authorised staff on the quiz page.
- [ ] Direct report URL works with a valid course-module ID.
- [ ] Students and unauthorised roles receive access denial.
- [ ] View and export capabilities can be assigned independently.
- [ ] Separate groups expose only permitted participants.
- [ ] Live refresh, manual refresh and pause/resume work.
- [ ] Optional browser alerts work and remain opt-in.
- [ ] CSV matches the visible authorised scope and cannot trigger spreadsheet formula injection.
- [ ] Report output is escaped and tested with unusual user names.

## F. Privacy, lifecycle and resilience

- [ ] Privacy API export returns the documented user data.
- [ ] User and context deletion operations remove the correct records.
- [ ] Retention task deletes only records older than the configured cutoff.
- [ ] Quiz attempt deletion closes/removes related monitoring data as designed.
- [ ] Quiz deletion removes settings, sessions and events.
- [ ] Backup/restore copies settings but not personal incident history.
- [ ] Data inventory in the listing and privacy template matches the database exactly.
- [ ] No external request or telemetry occurs.
- [ ] Data-protection officer reviews purpose, lawful basis, retention, permissions, transparency and DPIA need.

## G. Cross-platform matrix

Mark only environments actually tested:

- [ ] Moodle 4.5 + MariaDB/MySQL.
- [ ] Moodle 4.5 + PostgreSQL.
- [ ] Current Chrome on Windows.
- [ ] Current Edge on Windows.
- [ ] Current Firefox on Windows/Linux/macOS.
- [ ] Current Safari on macOS.
- [ ] Safari on iPadOS.
- [ ] Chrome on Android/ChromeOS.

For each: `[browser/version]` / `[OS/version]` / `[result]` / `[evidence]`.

## H. Publication artefacts

- [ ] English short and full descriptions have no placeholders.
- [ ] Spanish description has no placeholders.
- [ ] Installation, configuration, use, limitations and privacy are consistent across all documents.
- [ ] Screenshots use fictitious users and reveal no assessment content or identifiers.
- [ ] Release notes match the tagged source.
- [ ] Final ZIP checksum is recorded: `[SHA-256]`.
- [ ] Git tag is immutable and points to the tested source.
- [ ] Marketplace version compatibility lists only tested releases.

## Final approval

Release: `[version]`  
Tag: `[tag]`  
ZIP SHA-256: `[checksum]`  
Technical approver/date: `[name/date]`  
Educational owner/date: `[name/date]`  
Data-protection review/date: `[name or role/date]`  
Decision: `[approved / blocked with issue references]`
