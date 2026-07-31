# CDexamSave administrator and teacher guide

## 1. Purpose and boundary

CDexamSave records browser focus changes during real, in-progress Moodle quiz attempts. It helps staff identify attempts that require review. It does not block other applications, reveal what the student opened or prove misconduct by itself.

## 2. Administrator installation

1. Create a current backup and use a Moodle 4.5 staging copy.
2. Enable developer debugging on staging.
3. Upload the plugin ZIP through **Site administration > Plugins > Install plugins**.
4. Verify that Moodle detects `quizaccess_cdexamsave` and that the ZIP contains one top-level `cdexamsave` directory.
5. Complete the database upgrade and purge all caches.
6. Confirm that the scheduled task **Delete expired CDexamSave monitoring data** is listed and that cron runs.
7. Review role capabilities:
   - `quizaccess/cdexamsave:viewreport`
   - `quizaccess/cdexamsave:exportreport`
8. Limit CSV export to staff who need it.

## 3. Global settings

Review the CDexamSave section in the quiz access-rule settings:

- **Retention period:** how long completed monitoring data remains available.
- **Live report refresh:** teacher dashboard polling interval.
- **Student heartbeat:** frequency of active connection signals.
- **Disconnected threshold:** time without a heartbeat before an attempt is shown as disconnected.
- **Maximum incidents per attempt:** protection against malfunction or abuse.

Start with the defaults. Increase the report refresh interval to five or ten seconds if several teachers will monitor large cohorts simultaneously.

## 4. Enable a monitored quiz

1. Open the quiz and select **Edit settings**.
2. Expand **Extra restrictions on attempts > CDexamSave**.
3. Set **Enable focus monitoring** to Yes.
4. Keep **Warn the student after an incident** enabled unless a documented local policy says otherwise.
5. Choose a grace period. One second is a reasonable starting point; validate it with the devices used by the centre.
6. Save and display the quiz.

The quiz page should show a focus-monitoring notice. Authorised staff should also see **Open CDexamSave live report**. If needed, the direct path is:

`/mod/quiz/accessrule/cdexamsave/report.php?cmid=COURSE_MODULE_ID`

The course-module ID is the `id` in `/mod/quiz/view.php?id=123`.

## 5. Mandatory acceptance test

Use two different accounts and preferably two browser profiles:

1. A student starts a real quiz attempt. Do not use teacher preview.
2. A teacher opens the live report.
3. The student stays in the quiz and confirms a connected/active state.
4. The student changes tab for longer than the grace period.
5. The teacher confirms that the attempt shows focus loss within the refresh interval.
6. The student returns and acknowledges the notice.
7. The teacher confirms that the incident closes with a duration.
8. Export CSV and verify the same incident.
9. Repeat with a switch shorter than the grace period; no retained incident should remain.
10. Submit the quiz normally and confirm that navigation/submission does not create a persistent false incident.

Repeat on every supported browser/device combination. Keep redacted screenshots and the completed release checklist as evidence.

## 6. Teacher workflow during an exam

1. Open the live report before students begin.
2. If native browser alerts are wanted, enable them from the report and keep the page open.
3. Distinguish these states:
   - **Connected / Active:** the attempt is sending recent signals and has focus.
   - **Outside Moodle / Focus lost:** an incident is currently open.
   - **No recent signal:** the heartbeat threshold was exceeded; this can mean lost connectivity, device suspension or script interruption.
4. Record relevant context before contacting a student: duration, recurrence, device/network incident and classroom observation.
5. Use the CSV only when necessary and store it under the centre's assessment-data controls.

## 7. Interpreting incidents

Never treat one row as automatic evidence of cheating. A focus change can be caused by browser chrome, operating-system dialogs, accessibility software, notifications, battery/connection changes or mobile suspension. The plugin cannot identify the destination tab or application.

Use a documented human-review procedure. Consider pattern, duration, recurrence, question timing, technical context and the student's explanation. Do not create an automatic grade penalty or disciplinary action solely from CDexamSave data.

## 8. Troubleshooting

### The plugin appears to do nothing

- Confirm it is enabled in the specific quiz.
- Use a real student account and a real attempt; previews are excluded.
- Purge Moodle caches.
- Confirm JavaScript is enabled and no content-security policy blocks Moodle AMD modules.
- Open browser developer tools and check Console/Network for `quizaccess_cdexamsave/monitor` and `collector.php` errors.
- Confirm the attempt remains in the `inprogress` state.
- Confirm the site URL and HTTPS configuration are correct.
- Enable Moodle developer debugging on staging and reproduce the issue.

### The teacher cannot see the report

- Confirm `quizaccess/cdexamsave:viewreport` in the quiz activity context.
- Check that the rule is enabled and the quiz has been saved.
- Use the direct report URL with the course-module ID.
- If separate groups are used, confirm the teacher belongs to or can access the expected group.

### Incidents remain open

Mobile browsers can suspend background pages and delay the return signal. Confirm the student returned to the same active attempt and that connectivity recovered.

## 9. Uninstallation

Before uninstalling, export only the records the centre is required to retain. Follow the site's standard plugin-uninstall process. Confirm deletion behaviour on staging and document the result. Do not remove the plugin directory before Moodle has completed its database uninstall workflow.
