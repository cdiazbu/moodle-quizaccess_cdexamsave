# CD Exam Control change log

## 0.2.0 — 2026-10-10

Development beta for the independent quizaccess_cdexamcontrol component.

- Rebuilt the monitor with immediate listener registration and ordered acknowledged delivery.
- Retain unacknowledged lifecycle beacons and retry within a bounded per-tab, per-user, per-attempt queue.
- Combine visibility, focus and fullscreen into one observation; apply the quiz's grace period.
- Prevent delayed loss messages from reopening completed incidents; serialise collector requests using Moodle's lock API.
- Bind observation UUIDs to user, attempt and originating page.
- Accessible native dialogs with keyboard focus management, retryable fullscreen errors and a visible unsupported-browser fallback.
- Opt-in new-tab/window shortcut interception that preserves clipboard, zoom and keyboard navigation.
- Instantaneous shortcut observations do not count as time away.
- Preserve both new configuration fields in quiz backup/restore and upgrade older beta settings.
- Agreed-adjustment exemption capability, visible in the live report.
- Retention excludes in-progress and overdue attempts; enforce activity grouping boundaries in reports and exports.
- Escape spreadsheet formula-like names even with leading whitespace.
- Complete English and Spanish strings.
- JavaScript state regression scenarios and Moodle PHPUnit collector regressions.

The current candidate must pass Moodle integration and classroom browser checks before production use.
