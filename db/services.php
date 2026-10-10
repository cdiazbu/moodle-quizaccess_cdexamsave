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

/**
 * AJAX-enabled external functions for CD Exam Control.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'quizaccess_cdexamcontrol_record_signal' => [
        'classname' => 'quizaccess_cdexamcontrol\\external\\record_signal',
        'description' => 'Validate and record one browser focus-monitoring signal.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'quizaccess_cdexamcontrol_get_live_data' => [
        'classname' => 'quizaccess_cdexamcontrol\\external\\get_live_data',
        'description' => 'Return a group-aware live monitoring snapshot for authorised staff.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'quizaccess/cdexamcontrol:viewreport',
    ],
];
