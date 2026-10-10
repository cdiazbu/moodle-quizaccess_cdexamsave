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

namespace quizaccess_cdexamcontrol\local;

/**
 * Spreadsheet-safe rendering of user-controlled export values.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_export {
    /**
     * Prefix formula-like values, including those hidden by leading whitespace.
     *
     * @param string $value Untrusted cell text.
     * @return string
     */
    public static function safe_cell(string $value): string {
        return preg_match('/^[\s\x00-\x20]*[=+\-@]/u', $value) ? "'" . $value : $value;
    }
}
