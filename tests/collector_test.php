<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace quizaccess_cdexamcontrol;

use quizaccess_cdexamcontrol\local\incident_service;
use quizaccess_cdexamcontrol\local\report_service;

/**
 * Collector regressions using real Moodle quiz attempts and database records.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \quizaccess_cdexamcontrol\local\incident_service
 */
final class collector_test extends \advanced_testcase {
    /**
     * Build an enrolled student, a quiz question and an active attempt.
     *
     * @return array Attempt, module and base payload.
     */
    private function fixture(): array {
        global $DB, $PAGE;

        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quizobj = $quizgenerator->create_test_quiz([['Q1', 1, 'truefalse']], ['course' => $course->id]);
        $quiz = $quizobj->get_quiz();
        $this->setUser($student);
        $attempt = $quizgenerator->create_attempt($quiz->id, $student->id);
        $DB->set_field('quiz_attempts', 'timestart', time() - 120, ['id' => $attempt->id]);
        $DB->insert_record('quizaccess_cdexamcontrol', (object) [
            'quizid' => $quiz->id, 'enabled' => 1, 'warnstudent' => 1,
            'requirefullscreen' => 1, 'blockshortcuts' => 0, 'graceperiodms' => 1000,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $payload = [
            'attemptid' => $attempt->id, 'cmid' => $cm->id,
            'pagesessionid' => '123e4567-e89b-42d3-a456-426614174001',
            'eventuuid' => '123e4567-e89b-42d3-a456-426614174002',
            'reason' => 'window_blur', 'clienttime' => time(), 'duration' => 5,
        ];
        $PAGE = new \moodle_page();
        return [$attempt, $cm, $payload];
    }

    /**
     * A delayed loss cannot reopen a return already received by Moodle.
     *
     * @covers ::record
     * @return void
     */
    public function test_return_before_loss_does_not_reopen_session(): void {
        global $DB;

        [$attempt, , $payload] = $this->fixture();
        incident_service::record($payload + ['action' => 'returned']);
        incident_service::record($payload + ['action' => 'lost']);
        incident_service::record($payload + ['action' => 'returned']);
        $this->assertSame(1, $DB->count_records('quizaccess_cdexamcontrol_evt', ['attemptid' => $attempt->id]));
        $event = $DB->get_record('quizaccess_cdexamcontrol_evt', ['attemptid' => $attempt->id], '*', MUST_EXIST);
        $session = $DB->get_record('quizaccess_cdexamcontrol_ses', ['attemptid' => $attempt->id], '*', MUST_EXIST);
        $this->assertGreaterThan(0, (int) $event->timeend);
        $this->assertSame(0, (int) $session->focuslost);
    }

    /**
     * Ending one overlapping UUID must leave another open loss active.
     *
     * @covers ::record
     * @return void
     */
    public function test_return_does_not_clear_another_open_incident(): void {
        global $DB;

        [$attempt, , $payload] = $this->fixture();
        incident_service::record($payload + ['action' => 'lost']);
        $other = $payload;
        $other['eventuuid'] = '123e4567-e89b-42d3-a456-426614174003';
        incident_service::record($other + ['action' => 'lost']);
        incident_service::record($payload + ['action' => 'returned']);
        $session = $DB->get_record('quizaccess_cdexamcontrol_ses', ['attemptid' => $attempt->id], '*', MUST_EXIST);
        $this->assertSame(1, (int) $session->focuslost);
    }

    /**
     * Delayed old-page messages cannot overwrite a newer page's state.
     *
     * @covers ::record
     * @return void
     */
    public function test_old_page_does_not_take_over_new_page(): void {
        global $DB;

        [$attempt, , $payload] = $this->fixture();
        incident_service::record($payload + ['action' => 'init']);
        $newpage = $payload;
        $newpage['pagesessionid'] = '123e4567-e89b-42d3-a456-426614174004';
        incident_service::record($newpage + ['action' => 'init']);
        incident_service::record($payload + ['action' => 'lost']);
        $session = $DB->get_record('quizaccess_cdexamcontrol_ses', ['attemptid' => $attempt->id], '*', MUST_EXIST);
        $event = $DB->get_record('quizaccess_cdexamcontrol_evt', ['eventuuid' => $payload['eventuuid']], '*', MUST_EXIST);
        $this->assertGreaterThan(0, (int) $event->timeend);
        $this->assertSame($newpage['pagesessionid'], $session->pagesessionid);
        $this->assertSame(0, (int) $session->focuslost);
    }

    /**
     * A UUID is bound to its originating page as well as its user and attempt.
     *
     * @covers ::record
     * @return void
     */
    public function test_event_cannot_be_reused_by_another_page(): void {
        [, , $payload] = $this->fixture();
        incident_service::record($payload + ['action' => 'lost']);
        $payload['pagesessionid'] = '123e4567-e89b-42d3-a456-426614174004';
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invalidrequest', 'quizaccess_cdexamcontrol'));
        incident_service::record($payload + ['action' => 'returned']);
    }

    /**
     * A logged-in student cannot send signals for someone else's attempt.
     *
     * @covers ::record
     * @return void
     */
    public function test_foreign_attempt_is_rejected(): void {
        [, $cm, $payload] = $this->fixture();
        $other = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($other->id, $cm->course, 'student');
        $this->setUser($other);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('attemptnotmonitorable', 'quizaccess_cdexamcontrol'));
        incident_service::record($payload + ['action' => 'lost']);
    }

    /**
     * Intercepted shortcuts have zero duration and do not change focus state.
     *
     * @covers ::record
     * @return void
     */
    public function test_shortcut_is_an_instantaneous_observation(): void {
        global $DB;

        [$attempt, , $payload] = $this->fixture();
        $payload['reason'] = 'shortcut_blocked';
        incident_service::record($payload + ['action' => 'observed']);
        $event = $DB->get_record('quizaccess_cdexamcontrol_evt', ['attemptid' => $attempt->id], '*', MUST_EXIST);
        $session = $DB->get_record('quizaccess_cdexamcontrol_ses', ['attemptid' => $attempt->id], '*', MUST_EXIST);
        $this->assertSame(0, (int) $event->duration);
        $this->assertSame(0, (int) $session->focuslost);
    }

    /**
     * Exemption roles are respected by collection and shown to authorised staff.
     *
     * @covers ::record
     * @covers \quizaccess_cdexamcontrol\local\report_service::get_live_data
     * @return void
     */
    public function test_agreed_adjustment_is_visible_without_collecting(): void {
        [, $cm, $payload] = $this->fixture();
        global $USER;
        $studentid = (int) $USER->id;
        $context = \context_module::instance($cm->id);
        $roleid = create_role('Exam adjustment', 'examadjustment', '');
        assign_capability('quizaccess/cdexamcontrol:exempt', CAP_ALLOW, $roleid, $context->id);
        role_assign($roleid, $studentid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setAdminUser();
        $snapshot = report_service::get_live_data($cm);
        $this->assertSame('exempt', $snapshot['participants'][0]['status']);
        $this->setUser($studentid);
        $this->expectException(\moodle_exception::class);
        incident_service::record($payload + ['action' => 'lost']);
    }

    /**
     * Expired data for active attempts survives the retention job.
     *
     * @covers ::record
     * @covers \quizaccess_cdexamcontrol\task\cleanup::execute
     * @return void
     */
    public function test_retention_preserves_active_attempts(): void {
        global $DB;

        [$attempt, , $payload] = $this->fixture();
        incident_service::record($payload + ['action' => 'observed']);
        $past = time() - 5 * DAYSECS;
        $DB->set_field('quizaccess_cdexamcontrol_evt', 'timecreated', $past, ['attemptid' => $attempt->id]);
        $DB->set_field('quizaccess_cdexamcontrol_ses', 'timemodified', $past, ['attemptid' => $attempt->id]);
        set_config('retentiondays', 1, 'quizaccess_cdexamcontrol');
        $task = new \quizaccess_cdexamcontrol\task\cleanup();
        $task->execute();
        $this->assertTrue($DB->record_exists('quizaccess_cdexamcontrol_evt', ['attemptid' => $attempt->id]));
        $DB->set_field('quiz_attempts', 'state', 'finished', ['id' => $attempt->id]);
        $task->execute();
        $this->assertFalse($DB->record_exists('quizaccess_cdexamcontrol_evt', ['attemptid' => $attempt->id]));
        $this->assertFalse($DB->record_exists('quizaccess_cdexamcontrol_ses', ['attemptid' => $attempt->id]));
    }

    /**
     * The prior independent beta session table is migrated with its data intact.
     *
     * @covers ::record
     * @covers \xmldb_quizaccess_cdexamcontrol_upgrade
     * @return void
     */
    public function test_beta_session_table_upgrade_preserves_data(): void {
        global $CFG, $DB;

        [$attempt, , $payload] = $this->fixture();
        incident_service::record($payload + ['action' => 'observed']);
        $sessionid = (int) $DB->get_field('quizaccess_cdexamcontrol_ses', 'id', ['attemptid' => $attempt->id]);
        $dbman = $DB->get_manager();
        $dbman->rename_table(new \xmldb_table('quizaccess_cdexamcontrol_ses'), 'quizaccess_cdexamctrl_sess');
        set_config('version', 2026091900, 'quizaccess_cdexamcontrol');
        require_once($CFG->dirroot . '/mod/quiz/accessrule/cdexamcontrol/db/upgrade.php');
        \xmldb_quizaccess_cdexamcontrol_upgrade(2026091900);
        $this->assertFalse($dbman->table_exists(new \xmldb_table('quizaccess_cdexamctrl_sess')));
        $this->assertSame(
            $sessionid,
            (int) $DB->get_field('quizaccess_cdexamcontrol_ses', 'id', ['attemptid' => $attempt->id])
        );
        $this->assertSame(1, $DB->count_records('quizaccess_cdexamcontrol_evt', ['attemptid' => $attempt->id]));
    }

}
