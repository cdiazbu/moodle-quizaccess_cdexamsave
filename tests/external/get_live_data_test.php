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

namespace quizaccess_cdexamsave\external;

use core_external\external_api;

/**
 * Tests for the AJAX live-report external function.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \quizaccess_cdexamsave\external\get_live_data
 */
final class get_live_data_test extends \advanced_testcase {
    /**
     * An authorised teacher receives a return-schema-valid empty snapshot.
     *
     * @covers ::execute
     * @covers ::execute_returns
     * @return void
     */
    public function test_authorised_empty_live_snapshot(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $result = get_live_data::execute($cm->id, 0);
        $clean = external_api::clean_returnvalue(get_live_data::execute_returns(), $result);

        $this->assertSame(0, $clean['summary']['activeAttempts']);
        $this->assertSame(0, $clean['summary']['needsReview']);
        $this->assertSame([], $clean['participants']);
        $this->assertSame([], $clean['incidents']);
    }
}
