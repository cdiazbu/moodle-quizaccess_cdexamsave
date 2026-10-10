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
 * Site-wide settings for CD Exam Control.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'quizaccess_cdexamcontrol/general',
        get_string('settingsheading', 'quizaccess_cdexamcontrol'),
        get_string('settingsheading_desc', 'quizaccess_cdexamcontrol')
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamcontrol/retentiondays',
        get_string('retentiondays', 'quizaccess_cdexamcontrol'),
        get_string('retentiondays_desc', 'quizaccess_cdexamcontrol'),
        180,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamcontrol/reportrefresh',
        get_string('reportrefresh', 'quizaccess_cdexamcontrol'),
        get_string('reportrefresh_desc', 'quizaccess_cdexamcontrol'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamcontrol/heartbeatinterval',
        get_string('heartbeatinterval', 'quizaccess_cdexamcontrol'),
        get_string('heartbeatinterval_desc', 'quizaccess_cdexamcontrol'),
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamcontrol/staleseconds',
        get_string('staleseconds', 'quizaccess_cdexamcontrol'),
        get_string('staleseconds_desc', 'quizaccess_cdexamcontrol'),
        35,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamcontrol/maxincidents',
        get_string('maxincidents', 'quizaccess_cdexamcontrol'),
        get_string('maxincidents_desc', 'quizaccess_cdexamcontrol'),
        2000,
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'quizaccess_cdexamcontrol/reviewpriority',
        get_string('reviewprioritysettings', 'quizaccess_cdexamcontrol'),
        get_string('reviewprioritysettings_desc', 'quizaccess_cdexamcontrol')
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamcontrol/reviewincidentcount',
        get_string('reviewincidentcount', 'quizaccess_cdexamcontrol'),
        get_string('reviewincidentcount_desc', 'quizaccess_cdexamcontrol'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamcontrol/reviewduration',
        get_string('reviewduration', 'quizaccess_cdexamcontrol'),
        get_string('reviewduration_desc', 'quizaccess_cdexamcontrol'),
        60,
        PARAM_INT
    ));
}
