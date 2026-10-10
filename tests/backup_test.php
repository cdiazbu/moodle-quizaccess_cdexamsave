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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/phpunit/classes/restore_date_testcase.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/cdexamcontrol/rule.php');

/**
 * Real Moodle course backup and restore of independent quiz settings.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \backup_quizaccess_cdexamcontrol_subplugin
 * @covers \restore_quizaccess_cdexamcontrol_subplugin
 */
final class backup_test extends \restore_date_testcase {
    /**
     * All settings survive restore, without copying student observations.
     *
     * @return void
     */
    public function test_settings_round_trip_without_observations(): void {
        global $DB, $PAGE;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quizobj = $quizgenerator->create_test_quiz([['Q1', 1, 'truefalse']], ['course' => $course->id]);
        $quiz = $quizobj->get_quiz();
        \quizaccess_cdexamcontrol::save_settings((object) [
            'id' => $quiz->id, 'cdexamcontrolenabled' => 1, 'cdexamcontrolwarnstudent' => 0,
            'cdexamcontrolgraceperiodms' => 2000, 'cdexamcontrolrequirefullscreen' => 0,
            'cdexamcontrolblockshortcuts' => 1,
        ]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $attempt = $quizgenerator->create_attempt($quiz->id, $student->id);
        $PAGE = new \moodle_page();
        \quizaccess_cdexamcontrol\local\incident_service::record([
            'attemptid' => $attempt->id, 'action' => 'observed',
            'pagesessionid' => '123e4567-e89b-42d3-a456-426614174010',
            'eventuuid' => '123e4567-e89b-42d3-a456-426614174011',
            'reason' => 'shortcut_blocked',
        ]);
        $this->setAdminUser();
        $newcourseid = $this->backup_and_restore($course);
        $newquiz = $DB->get_record('quiz', ['course' => $newcourseid], '*', MUST_EXIST);
        $settings = $DB->get_record('quizaccess_cdexamcontrol', ['quizid' => $newquiz->id], '*', MUST_EXIST);
        $this->assertSame(1, (int) $settings->enabled);
        $this->assertSame(0, (int) $settings->warnstudent);
        $this->assertSame(2000, (int) $settings->graceperiodms);
        $this->assertSame(0, (int) $settings->requirefullscreen);
        $this->assertSame(1, (int) $settings->blockshortcuts);
        $this->assertSame(0, $DB->count_records('quizaccess_cdexamcontrol_evt', ['quizid' => $newquiz->id]));
        $this->assertSame(0, $DB->count_records('quizaccess_cdexamcontrol_ses', ['quizid' => $newquiz->id]));
        $this->assertSame(1, $DB->count_records('quizaccess_cdexamcontrol_evt', ['quizid' => $quiz->id]));
    }
}
