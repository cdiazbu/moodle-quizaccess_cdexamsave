<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_cdexamsave;

/**
 * Tests for per-quiz rule configuration.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno <carlosdiazbueno@gmail.com>
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
