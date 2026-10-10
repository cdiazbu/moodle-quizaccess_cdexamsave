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
 * Tests for report prioritisation logic.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \quizaccess_cdexamcontrol\local\report_service
 */
final class report_service_test extends \advanced_testcase {
    /**
     * Review priority honours every configured trigger without classifying
     * the underlying behaviour.
     *
     * @covers ::needs_review
     * @return void
     */
    public function test_review_priority_thresholds(): void {
        $thresholds = ['incidentcount' => 3, 'duration' => 60];

        $this->assertFalse(report_service::needs_review(2, 59, false, $thresholds));
        $this->assertTrue(report_service::needs_review(3, 10, false, $thresholds));
        $this->assertTrue(report_service::needs_review(1, 60, false, $thresholds));
        $this->assertTrue(report_service::needs_review(0, 0, true, $thresholds));
    }

    /**
     * Site settings are bounded before they affect report ordering.
     *
     * @covers ::get_review_thresholds
     * @return void
     */
    public function test_review_thresholds_are_bounded(): void {
        $this->resetAfterTest(true);
        set_config('reviewincidentcount', 999, 'quizaccess_cdexamcontrol');
        set_config('reviewduration', 999999, 'quizaccess_cdexamcontrol');

        $this->assertSame([
            'incidentcount' => 100,
            'duration' => DAYSECS,
        ], report_service::get_review_thresholds());
    }
}
