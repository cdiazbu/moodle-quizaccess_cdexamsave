# Proposed directory listing — CD Exam Control

## Short description

Observe browser focus and fullscreen changes during Moodle quizzes, with live teacher reports, configurable grace periods and CSV exports.

## Description

CD Exam Control is an independent quiz access rule for Moodle 4.5. Monitoring is enabled per quiz and is off by default. It combines visibility, focus and fullscreen signals into observations, filters brief changes using a configurable grace period, and supports optional student notices.

Teachers with the appropriate permissions can view a live report and export observations or attempt summaries. Reports respect Moodle group permissions. Individual students can be exempted through a module-context capability to support agreed adjustments. Settings are included in quiz backup/restore; personal observation history is not copied into a restored assessment.

Fullscreen requires a student gesture. Unsupported browsers show a limitation and allow the attempt to continue. Optional shortcut interception is off by default and preserves copy, paste, zoom and keyboard navigation. A normal webpage cannot reliably block other applications, browser-reserved actions or another device.

Observations and estimated durations support human review; they are not proof of misconduct. The plugin does not change grades, save answers or submit attempts. Moodle continues to own the assessment workflow. Network or browser failures may delay or lose unacknowledged observations.

The plugin uses Moodle AJAX external services, its privacy API and scheduled retention. It does not collect camera, microphone, screenshots, browsing history or fingerprints, and does not use external monitoring services.

Version 0.2.0 is a beta. Real-device and classroom acceptance is required before use in a consequential assessment. Compatibility with later Moodle releases is not yet claimed.

Author: Carlos Díaz Bueno. Licence: GPL v3 or later.
This text is a submission draft, not evidence of directory approval.
