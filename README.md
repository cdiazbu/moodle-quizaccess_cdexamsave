# CD Exam Control

Independent Moodle quiz access plugin: component quizaccess_cdexamcontrol, directory cdexamcontrol.
Author: Carlos Díaz Bueno. GPL v3 or later. Version 0.2.0, development beta.

The project is independent of CD ExamFocus (quizaccess_cdexamsave). Its installed data is not migrated or renamed. The intended repository is cdiazbu/moodle-quizaccess_cdexamcontrol; until it is created, development evidence is held in the development/cdexamcontrol-0.2.0 branch of the previous repository. That branch must not be merged into the ExamFocus release branch.

## Behaviour

Monitoring is off by default and enabled separately in each quiz. When enabled:

- Browser visibility, focus and fullscreen changes are combined into observations after a configurable grace period.
- Fullscreen is requested through an accessible prompt. If the browser cannot support it, the student can continue and sees the limitation.
- New-tab/window shortcut interception is optional and off by default. It preserves copy, paste, zoom, refresh and normal keyboard navigation.
- Authorised staff can view the live report, filter by permitted groups and export observations or attempt summaries.
- English is the canonical bundled language. The Spanish translation is supplied separately for AMOS import after the new component is registered, following the ExamFocus publication process.
- Staff can grant an individual exemption through a module-context role with quizaccess/cdexamcontrol:exempt for an agreed adjustment. The live report identifies the exemption.
- Moodle owns answers, autosave, quiz timing and submission. The plugin does not change grades or submit attempts.

A normal web page cannot guarantee prevention of application switching, browser shortcuts, developer tools, remote access or use of another device. Fullscreen and focus signals are browser observations, not proof of misconduct. Browser and network failures may delay observations and affect estimated durations. Use Moodle's Safe Exam Browser integration when the assessment requires a managed secure-browser policy.

## Collection and privacy

The authenticated collector validates user, attempt, course module, attempt state, page-session and observation UUIDs. An observation is not accepted for another student's attempt or a preview/finished attempt. Moodle locks and database transactions serialise concurrent collector requests.

A small retry queue uses sessionStorage, scoped to the student and attempt in the current tab. It stores payloads without the Moodle session key, up to 100 pending messages for 30 minutes. Storage failures preserve online collection. A beacon is retained until an acknowledged retry; browser acceptance of a beacon is not confirmation of server receipt. Closing the tab permanently can lose unacknowledged payloads. The badge indicates pending delivery and queue saturation.

Stored server data comprises user/quiz/attempt IDs, random page and observation IDs, browser reason, timestamps and estimated duration. There is no camera, microphone, screenshot, device fingerprint, browsing-history or external service collection. Moodle's privacy API supports export and deletion. Scheduled retention preserves in-progress and overdue attempts.

## Install or update on the clone

Use a packaged candidate generated after CI passes; do not treat an untested development source archive as a release.

1. Take a database and code backup of the clone.
2. Upload the candidate ZIP under Site administration → Plugins → Install plugins, or place the cdexamcontrol folder under mod/quiz/accessrule/.
3. Run Moodle's upgrade through Site administration → Notifications.
4. Purge caches and check that the component is quizaccess_cdexamcontrol.
5. Enable the plugin in a dedicated trial quiz and run the teacher/student checks in TESTING.md.

Updating beta 0.1.0 keeps the same independent component and recorded data. The upgrade renames the abbreviated beta session table to a component-prefixed name and adds missing fullscreen/shortcut fields defensively and preserves existing settings. No change is made to the ExamFocus component.

## Development and release

Target: Moodle 4.5. Compatibility with later Moodle versions must be verified before it is advertised.

~~~sh
node tests/js/run.cjs
python3 tools/validate_release.py
# Run inside an installed Moodle checkout:
npx grunt amd --root=mod/quiz/accessrule/cdexamcontrol
# Then run the Moodle Plugin CI/PHPUnit checks defined by the workflow.
python3 tools/build_release.py
~~~

The canonical package is CD-Exam-Control-0.2.0.zip with a cdexamcontrol/ top-level folder. The workflow verifies checked-in AMD against Moodle Grunt before packaging. The builder requires matching source maps and excludes tests, tooling and GitHub workflows from the installable ZIP. The Spanish AMOS import ZIP contains only quizaccess_cdexamcontrol.php at its root.

See TESTING.md for acceptance criteria and docs/GUIA_PROFESORADO_ES.md for classroom use.

The manual publish-beta workflow is restricted to main in the independent repository. It requires successful CI for the exact commit and an explicit declaration that clone acceptance is recorded. It creates a prerelease and refuses to replace an existing v0.2.0 tag. No beta publication is scheduled automatically.
