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

use quizaccess_cdexamcontrol\local\report_service;

/**
 * Report permission and grouping-boundary regressions.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \quizaccess_cdexamcontrol\local\report_service
 */
final class report_access_test extends \advanced_testcase {
    /**
     * Students cannot read the teacher report.
     *
     * @covers ::get_live_data
     * @return void
     */
    public function test_student_report_access_is_rejected(): void {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $this->expectException(\required_capability_exception::class);
        report_service::get_live_data($cm);
    }

    /**
     * Same-course groups outside an activity's grouping are rejected.
     *
     * @covers ::get_live_data
     * @return void
     */
    public function test_group_must_belong_to_the_activity_grouping(): void {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $grouping = $generator->create_grouping(['courseid' => $course->id]);
        $group = $generator->create_group(['courseid' => $course->id]);
        $quiz = $generator->create_module('quiz', [
            'course' => $course->id, 'groupmode' => SEPARATEGROUPS, 'groupingid' => $grouping->id,
        ]);
        $this->setAdminUser();
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invalidgroup', 'quizaccess_cdexamcontrol'));
        report_service::get_live_data($cm, $group->id);
    }
}
