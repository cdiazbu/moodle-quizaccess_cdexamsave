# CD Exam Control acceptance and validation

## Automated checks

- tests/js/run.cjs exercises browser-state simulations: delayed initial AJAX, grace filtering, overlapping visibility/focus signals, fullscreen recovery, offline queue ordering, accessibility shortcuts, unsupported fullscreen, cancelled submission, intentional Moodle navigation and unacknowledged beacons.
- Moodle PHPUnit collector tests use a generated quiz question and a real attempt: out-of-order/duplicate deliveries, overlapping UUIDs, stale page-session isolation, UUID page ownership, foreign-user rejection, instantaneous shortcut observations, exemption reporting and retention.
- Existing rule, report and external-function tests are retained under the independent component.
- Release checks validate metadata, XMLDB table names, string references, translation completeness, PNG integrity and AMD source maps.
- CI runs PHP lint, Moodle code/PHPDoc checks, metadata checks, savepoint checks, Grunt and PHPUnit.

The JavaScript harness simulates DOM/API contracts; it does not demonstrate browser shortcut enforcement. Real browser and Moodle integration checks remain necessary.

## Clone acceptance

Use a dedicated course and quiz on clon.aula.sagradafamiliasiervas.es. Record Moodle, PHP and browser versions and use an actual enrolled student account, not a teacher preview.

1. Install on a clean clone and update from the 0.1.0 beta. Check notifications, capability registration and schema; preserve prior beta observations.
2. Teacher: enable monitoring, leave shortcut restrictions off, use a 1-second grace period. Student: start, answer and navigate between question pages; no page-transition observations should be created.
3. Verify brief focus changes are ignored and sustained tab/window changes yield one observation each. Confirm that fullscreen remains outstanding until it is restored.
4. Test a browser refusing fullscreen and a browser without the API; the refusal must offer a retry and the unsupported case must permit answering with a visible limitation.
5. Disconnect the network, generate and complete an observation, reconnect and confirm ordered deduplicated delivery. Confirm the badge distinguishes connecting/pending/connected.
6. Cancel form validation and confirm monitoring resumes. Submit normally and by Moodle's timer; check answers and grades are unchanged by the plugin.
7. Enable optional shortcut interception. Check Ctrl/Cmd+T/N and new-context link clicks where the browser exposes those events. Verify copy/paste, zoom, refresh, Tab, Shift+Tab and screen-reader navigation still work.
8. Test Chrome, Firefox, Edge and Safari on the actual devices used by students, including touch devices if relevant. Document unsupported or browser-reserved actions.
9. Teacher: separate groups and activity groupings must restrict reports, notifications and both exports. Student accounts must not read reports or someone else's data.
10. Grant one student the exemption capability at the quiz context. Confirm no collection and an explicit adjustment label in the teacher report.
11. Backup and restore a quiz, preserving enabled, warnstudent, graceperiodms, requirefullscreen and blockshortcuts. Ensure observations are not copied into a new assessment.
12. Exercise Moodle privacy export/deletion and scheduled retention; active/overdue attempt data must remain.
13. Test screen-reader announcements, native-dialog focus containment, keyboard retry, zoom, large text and Moodle theme compatibility.

Do not label this beta production-ready until these checks pass. No automated result substitutes for a recorded clone installation and student attempt.
