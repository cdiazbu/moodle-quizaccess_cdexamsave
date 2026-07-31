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
 * Teacher-facing live report.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('quizaccess/cdexamsave:viewreport', $context);

$groupid = groups_get_activity_group($cm, true);
$url = new moodle_url('/mod/quiz/accessrule/cdexamsave/report.php', ['cmid' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('reportfor', 'quizaccess_cdexamsave', format_string($quiz->name)));
$PAGE->set_heading(format_string($course->fullname));

$refreshseconds = (int) get_config('quizaccess_cdexamsave', 'reportrefresh');
$refreshseconds = max(2, min(30, $refreshseconds ?: 3));
$liveurl = new moodle_url('/mod/quiz/accessrule/cdexamsave/live.php', [
    'cmid' => $cm->id,
    'group' => $groupid,
]);
$exporturl = new moodle_url('/mod/quiz/accessrule/cdexamsave/export.php', [
    'cmid' => $cm->id,
    'group' => $groupid,
]);
$PAGE->requires->js_call_amd('quizaccess_cdexamsave/live_report', 'init', [[
    'liveUrl' => $liveurl->out(false),
    'refreshMs' => $refreshseconds * 1000,
    'strings' => [
        'live' => get_string('live', 'quizaccess_cdexamsave'),
        'paused' => get_string('paused', 'quizaccess_cdexamsave'),
        'lastUpdated' => get_string('lastupdated', 'quizaccess_cdexamsave', '{$a}'),
        'pause' => get_string('pauserefresh', 'quizaccess_cdexamsave'),
        'resume' => get_string('resumerefresh', 'quizaccess_cdexamsave'),
        'notificationsEnabled' => get_string('notificationsenabled', 'quizaccess_cdexamsave'),
        'notificationsDenied' => get_string('notificationsdenied', 'quizaccess_cdexamsave'),
        'notificationTitle' => get_string('notificationtitle', 'quizaccess_cdexamsave'),
        'notificationBody' => get_string('notificationbody', 'quizaccess_cdexamsave', (object) [
            'student' => '{$student}',
            'reason' => '{$reason}',
        ]),
        'noAttempts' => get_string('noactiveattempts', 'quizaccess_cdexamsave'),
        'noIncidents' => get_string('noincidents', 'quizaccess_cdexamsave'),
        'pollError' => get_string('pollerror', 'quizaccess_cdexamsave'),
    ],
]]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('livereport', 'quizaccess_cdexamsave'));
echo html_writer::tag('p', get_string('reportintro', 'quizaccess_cdexamsave'), ['class' => 'lead']);

if (!$DB->record_exists('quizaccess_cdexamsave', ['quizid' => $quiz->id, 'enabled' => 1])) {
    echo $OUTPUT->notification(get_string('reportdisabled', 'quizaccess_cdexamsave'), 'warning');
}

if (groups_get_activity_groupmode($cm)) {
    echo html_writer::start_div('mb-3');
    groups_print_activity_menu($cm, $url);
    echo html_writer::end_div();
}

echo html_writer::start_div('cdexamsave-report', ['id' => 'cdexamsave-report']);
echo html_writer::start_div('cdexamsave-toolbar');
echo html_writer::tag('span', get_string('live', 'quizaccess_cdexamsave'), [
    'id' => 'cdexamsave-live-state',
    'class' => 'cdexamsave-live-pill',
]);
echo html_writer::tag('span', '', ['id' => 'cdexamsave-updated', 'aria-live' => 'polite']);
echo html_writer::start_div('cdexamsave-toolbar-actions');
echo html_writer::tag('button', get_string('refreshnow', 'quizaccess_cdexamsave'), [
    'type' => 'button',
    'id' => 'cdexamsave-refresh',
    'class' => 'btn btn-secondary',
]);
echo html_writer::tag('button', get_string('pauserefresh', 'quizaccess_cdexamsave'), [
    'type' => 'button',
    'id' => 'cdexamsave-pause',
    'class' => 'btn btn-outline-secondary',
]);
echo html_writer::tag('button', get_string('enablenotifications', 'quizaccess_cdexamsave'), [
    'type' => 'button',
    'id' => 'cdexamsave-notifications',
    'class' => 'btn btn-outline-secondary',
]);
if (has_capability('quizaccess/cdexamsave:exportreport', $context)) {
    echo html_writer::link($exporturl, get_string('exportcsv', 'quizaccess_cdexamsave'), [
        'class' => 'btn btn-primary',
    ]);
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div('', 'alert alert-danger d-none', [
    'id' => 'cdexamsave-error',
    'role' => 'alert',
]);

$cards = [
    'active' => 'activeattempts',
    'attention' => 'attentionnow',
    'connected' => 'connectedattempts',
    'incidents' => 'totalincidents',
];
echo html_writer::start_div('cdexamsave-summary');
foreach ($cards as $id => $stringkey) {
    echo html_writer::start_div('cdexamsave-card cdexamsave-card-' . $id);
    echo html_writer::tag('span', '0', [
        'id' => 'cdexamsave-count-' . $id,
        'class' => 'cdexamsave-card-value',
    ]);
    echo html_writer::tag('span', get_string($stringkey, 'quizaccess_cdexamsave'), [
        'class' => 'cdexamsave-card-label',
    ]);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::tag('h3', get_string('participants', 'quizaccess_cdexamsave'), ['class' => 'mt-4']);
echo html_writer::start_div('table-responsive');
echo html_writer::start_tag('table', ['class' => 'table table-striped cdexamsave-table']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach (['student', 'attempt', 'connection', 'focusstate', 'incidentcount', 'totaltimeaway', 'lastheartbeat'] as $key) {
    echo html_writer::tag('th', get_string($key, 'quizaccess_cdexamsave'), ['scope' => 'col']);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::tag('tbody', '', ['id' => 'cdexamsave-participants-body']);
echo html_writer::end_tag('table');
echo html_writer::end_div();

echo html_writer::tag('h3', get_string('recentincidents', 'quizaccess_cdexamsave'), ['class' => 'mt-4']);
echo html_writer::start_div('table-responsive');
echo html_writer::start_tag('table', ['class' => 'table table-sm table-hover cdexamsave-table']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach (['student', 'attempt', 'started', 'ended', 'duration', 'reason'] as $key) {
    echo html_writer::tag('th', get_string($key, 'quizaccess_cdexamsave'), ['scope' => 'col']);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::tag('tbody', '', ['id' => 'cdexamsave-incidents-body']);
echo html_writer::end_tag('table');
echo html_writer::end_div();

echo html_writer::div(get_string('privacywarning', 'quizaccess_cdexamsave'), 'alert alert-light mt-4');
echo html_writer::tag('noscript', get_string('noscript', 'quizaccess_cdexamsave'), ['class' => 'alert alert-warning']);
echo html_writer::end_div();
echo $OUTPUT->footer();
