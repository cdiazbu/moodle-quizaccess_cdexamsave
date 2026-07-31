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

namespace quizaccess_cdexamsave;

/**
 * Tests for per-quiz rule configuration.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \quizaccess_cdexamsave
 */
final class rule_test extends \advanced_testcase {
    /**
     * Load the legacy rule entry point used by Moodle's access manager.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/accessrule/cdexamsave/rule.php');
    }

    /**
     * Settings are inserted, updated and removed without duplicates.
     *
     * @covers ::save_settings
     * @covers ::delete_settings
     * @return void
     */
    public function test_save_update_and_disable_settings(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $formdata = (object) [
            'id' => $quiz->id,
            'cdexamsaveenabled' => 1,
            'cdexamsavewarnstudent' => 1,
            'cdexamsavegraceperiodms' => 1000,
        ];
        \quizaccess_cdexamsave::save_settings($formdata);
        $record = $DB->get_record('quizaccess_cdexamsave', ['quizid' => $quiz->id], '*', MUST_EXIST);
        $this->assertSame(1, (int) $record->enabled);
        $this->assertSame(1, (int) $record->warnstudent);
        $this->assertSame(1000, (int) $record->graceperiodms);

        $formdata->cdexamsavewarnstudent = 0;
        $formdata->cdexamsavegraceperiodms = 2000;
        \quizaccess_cdexamsave::save_settings($formdata);
        $this->assertSame(1, $DB->count_records('quizaccess_cdexamsave', ['quizid' => $quiz->id]));
        $record = $DB->get_record('quizaccess_cdexamsave', ['quizid' => $quiz->id], '*', MUST_EXIST);
        $this->assertSame(0, (int) $record->warnstudent);
        $this->assertSame(2000, (int) $record->graceperiodms);

        $formdata->cdexamsaveenabled = 0;
        \quizaccess_cdexamsave::save_settings($formdata);
        $this->assertFalse($DB->record_exists('quizaccess_cdexamsave', ['quizid' => $quiz->id]));
    }

    /**
     * The access manager receives an efficient left join and namespaced fields.
     *
     * @covers ::get_settings_sql
     * @return void
     */
    public function test_settings_sql_is_namespaced(): void {
        [$fields, $joins, $params] = \quizaccess_cdexamsave::get_settings_sql(42);
        $this->assertStringContainsString('cdexamsaveenabled', $fields);
        $this->assertStringContainsString('{quizaccess_cdexamsave}', $joins);
        $this->assertSame([], $params);
    }
}
