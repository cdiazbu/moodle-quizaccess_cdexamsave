<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Site-wide settings for CDexamSave.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno <carlosdiazbueno@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'quizaccess_cdexamsave/general',
        get_string('settingsheading', 'quizaccess_cdexamsave'),
        get_string('settingsheading_desc', 'quizaccess_cdexamsave')
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamsave/retentiondays',
        get_string('retentiondays', 'quizaccess_cdexamsave'),
        get_string('retentiondays_desc', 'quizaccess_cdexamsave'),
        180,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamsave/reportrefresh',
        get_string('reportrefresh', 'quizaccess_cdexamsave'),
        get_string('reportrefresh_desc', 'quizaccess_cdexamsave'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamsave/heartbeatinterval',
        get_string('heartbeatinterval', 'quizaccess_cdexamsave'),
        get_string('heartbeatinterval_desc', 'quizaccess_cdexamsave'),
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamsave/staleseconds',
        get_string('staleseconds', 'quizaccess_cdexamsave'),
        get_string('staleseconds_desc', 'quizaccess_cdexamsave'),
        35,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'quizaccess_cdexamsave/maxincidents',
        get_string('maxincidents', 'quizaccess_cdexamsave'),
        get_string('maxincidents_desc', 'quizaccess_cdexamsave'),
        2000,
        PARAM_INT
    ));
}
