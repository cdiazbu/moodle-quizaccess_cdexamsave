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

use quizaccess_cdexamcontrol\local\csv_export;

/**
 * Export regression checks for untrusted spreadsheet cells.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \quizaccess_cdexamcontrol\local\csv_export
 */
final class csv_export_test extends \advanced_testcase {
    /**
     * Leading whitespace must not hide a spreadsheet formula.
     *
     * @covers ::safe_cell
     * @return void
     */
    public function test_formula_like_values_are_escaped(): void {
        foreach (['=1+1', '+1', '-1', '@SUM(1)', " \t=1+1", "\n=1+1"] as $value) {
            $this->assertSame("'" . $value, csv_export::safe_cell($value));
        }
        $this->assertSame('María Díaz', csv_export::safe_cell('María Díaz'));
        $this->assertSame(' Ana', csv_export::safe_cell(' Ana'));
    }
}
