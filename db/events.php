<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Event observers for CD ExamFocus.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\mod_quiz\\event\\attempt_submitted',
        'callback' => '\\quizaccess_cdexamsave\\observer::attempt_finished',
        'priority' => 9999,
    ],
    [
        'eventname' => '\\mod_quiz\\event\\attempt_abandoned',
        'callback' => '\\quizaccess_cdexamsave\\observer::attempt_finished',
        'priority' => 9999,
    ],
    [
        'eventname' => '\\mod_quiz\\event\\attempt_deleted',
        'callback' => '\\quizaccess_cdexamsave\\observer::attempt_deleted',
        'priority' => 9999,
    ],
];
