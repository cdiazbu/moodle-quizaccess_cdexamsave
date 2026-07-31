# Security policy

## Supported versions

Security fixes are provided for the most recent CD ExamFocus release on the Moodle versions explicitly listed as supported in its public release record. Sites should also run a Moodle and PHP branch that still receives security updates.

## Reporting a vulnerability

Do not open a public issue for a suspected vulnerability or attach student data, session identifiers, request bodies, database exports or credentials.

Use GitHub's private vulnerability reporting for this repository with:

- CD ExamFocus, Moodle, PHP and database versions;
- a concise description and security impact;
- exact reproduction steps or a minimal proof of concept;
- relevant logs with personal data and secrets removed;
- whether exploitation requires a student, teacher, manager or administrator account.

The maintainer aims to acknowledge receipt within 5 working days and provide an initial status update within 10 working days. Timelines depend on severity, reproducibility and coordinated disclosure needs.

## Scope

Examples in scope include authorisation bypass, cross-group disclosure, stored or reflected injection, cross-site request forgery, SQL injection, privacy API failure, data-retention failure, incident impersonation, session leakage and unsafe CSV output.

The documented limitation that client-side monitoring can be disabled or bypassed on an unmanaged device is not by itself a vulnerability. CD ExamFocus is not a locked browser and does not claim to provide a trusted client.
