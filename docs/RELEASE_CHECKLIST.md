# CD Exam Control release evidence

Version: 0.2.0 beta. Build: 2026101000. Component: quizaccess_cdexamcontrol.
Author: Carlos Díaz Bueno. Licence: GPL v3 or later.

This checklist follows the ExamFocus process. Mark only completed checks with evidence. A beta does not imply that classroom acceptance or public directory review has passed.

## Automated gates

- [ ] Ten JavaScript state regression scenarios pass.
- [ ] Release metadata, XMLDB names, English strings, PNG integrity and matching AMD source maps pass validation.
- [ ] PHP lint, Moodle coding standards, PHPDoc, metadata and savepoints pass.
- [ ] Moodle Grunt verifies the committed AMD with no lint warnings or stale builds.
- [ ] Twenty Moodle PHPUnit tests pass on PHP 8.1, 8.2 and 8.3 with PostgreSQL and MariaDB.
- [ ] The deterministic ZIP contains a single cdexamcontrol/ root, compiled AMD and source maps; no nested archives, credentials, development caches or extra language folders.
- [ ] Spanish AMOS ZIP contains quizaccess_cdexamcontrol.php at its root and exactly matches the 162 English string keys.
- [ ] Package SHA-256 and the exact tested Git commit are recorded in VALIDATION.md.

## Repository and beta delivery

- [ ] Independent public repository cdiazbu/moodle-quizaccess_cdexamcontrol exists and has the plugin at its root.
- [ ] Issue tracker, documentation links, licence and authorship are correct.
- [ ] Main and pull requests run the same automated gates.
- [ ] Source and beta ZIP are synchronised to the new project.
- [ ] A prerelease tag points to the verified source; an existing tag is never overwritten.

## Clone and educational acceptance

- [ ] Fresh installation and upgrade from 0.1.0 complete on clon.aula.sagradafamiliasiervas.es.
- [ ] Real student attempts pass the browser, fullscreen, offline delivery, answer navigation and submission checks in TESTING.md.
- [ ] Teacher reports and CSV exports respect assigned capabilities, separate groups and activity groupings.
- [ ] Screen-reader use, keyboard navigation and agreed individual exemptions are verified on actual devices.
- [ ] Privacy export/deletion, retention, backup/restore and uninstall are checked on the clone.
- [ ] The assessment owner records the classroom acceptance outcome and applicable data-handling decisions.

## Moodle Plugins Directory and AMOS

- [ ] Listing text states browser limitations and human interpretation of observations.
- [ ] Screenshots use fictitious accounts and reveal no assessment material.
- [ ] A new plugin entry is registered for quizaccess_cdexamcontrol, independently of ExamFocus.
- [ ] Moodle's review outcome is recorded before claiming approval.
- [ ] Spanish strings are imported to AMOS once the new component is available there; keep only lang/en in the public plugin source and installable ZIP.
- [ ] Compatibility claims list only verified Moodle versions. Moodle 5.x is not yet verified.

Do not carry over the old plugin's review approval, installation counts or Marketplace history to this new component.
