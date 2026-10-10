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
 * @package    quizaccess_cdexamcontrol
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
require_capability('quizaccess/cdexamcontrol:viewreport', $context);

$groupid = groups_get_activity_group($cm, true);
$url = new moodle_url('/mod/quiz/accessrule/cdexamcontrol/report.php', ['cmid' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('reportfor', 'quizaccess_cdexamcontrol', format_string($quiz->name)));
$PAGE->set_heading(format_string($course->fullname));

$refreshseconds = (int) get_config('quizaccess_cdexamcontrol', 'reportrefresh');
$refreshseconds = max(2, min(30, $refreshseconds ?: 3));
$exportparams = [
    'cmid' => $cm->id,
    'group' => $groupid,
];
$incidentexporturl = new moodle_url(
    '/mod/quiz/accessrule/cdexamcontrol/export.php',
    $exportparams + ['mode' => 'incidents']
);
$summaryexporturl = new moodle_url(
    '/mod/quiz/accessrule/cdexamcontrol/export.php',
    $exportparams + ['mode' => 'summary']
);
$PAGE->requires->js_call_amd('quizaccess_cdexamcontrol/live_report', 'init', [[
    'cmId' => (int) $cm->id,
    'groupId' => (int) $groupid,
    'refreshMs' => $refreshseconds * 1000,
    'strings' => [
        'live' => get_string('live', 'quizaccess_cdexamcontrol'),
        'paused' => get_string('paused', 'quizaccess_cdexamcontrol'),
        'lastUpdated' => get_string('lastupdated', 'quizaccess_cdexamcontrol', '{$a}'),
        'pause' => get_string('pauserefresh', 'quizaccess_cdexamcontrol'),
        'resume' => get_string('resumerefresh', 'quizaccess_cdexamcontrol'),
        'notificationsEnabled' => get_string('notificationsenabled', 'quizaccess_cdexamcontrol'),
        'notificationsDenied' => get_string('notificationsdenied', 'quizaccess_cdexamcontrol'),
        'notificationTitle' => get_string('notificationtitle', 'quizaccess_cdexamcontrol'),
        'notificationBody' => get_string('notificationbody', 'quizaccess_cdexamcontrol', (object) [
            'student' => '{$student}',
            'reason' => '{$reason}',
        ]),
        'noAttempts' => get_string('noactiveattempts', 'quizaccess_cdexamcontrol'),
        'noFilteredAttempts' => get_string('nofilteredattempts', 'quizaccess_cdexamcontrol'),
        'noIncidents' => get_string('noincidents', 'quizaccess_cdexamcontrol'),
        'pollError' => get_string('pollerror', 'quizaccess_cdexamcontrol'),
    ],
]]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('livereport', 'quizaccess_cdexamcontrol'));
echo html_writer::tag('p', get_string('reportintro', 'quizaccess_cdexamcontrol'), ['class' => 'lead']);

if (!$DB->record_exists('quizaccess_cdexamcontrol', ['quizid' => $quiz->id, 'enabled' => 1])) {
    echo $OUTPUT->notification(get_string('reportdisabled', 'quizaccess_cdexamcontrol'), 'warning');
}

if (groups_get_activity_groupmode($cm)) {
    echo html_writer::start_div('mb-3');
    groups_print_activity_menu($cm, $url);
    echo html_writer::end_div();
}

echo html_writer::start_div('cdexamcontrol-report', ['id' => 'cdexamcontrol-report']);
echo html_writer::start_div('cdexamcontrol-toolbar');
echo html_writer::tag('span', get_string('live', 'quizaccess_cdexamcontrol'), [
    'id' => 'cdexamcontrol-live-state',
    'class' => 'cdexamcontrol-live-pill',
]);
echo html_writer::tag('span', '', ['id' => 'cdexamcontrol-updated', 'aria-live' => 'polite']);
echo html_writer::start_div('cdexamcontrol-toolbar-actions');
echo html_writer::tag('button', get_string('refreshnow', 'quizaccess_cdexamcontrol'), [
    'type' => 'button',
    'id' => 'cdexamcontrol-refresh',
    'class' => 'btn btn-secondary',
]);
echo html_writer::tag('button', get_string('pauserefresh', 'quizaccess_cdexamcontrol'), [
    'type' => 'button',
    'id' => 'cdexamcontrol-pause',
    'class' => 'btn btn-outline-secondary',
]);
echo html_writer::tag('button', get_string('enablenotifications', 'quizaccess_cdexamcontrol'), [
    'type' => 'button',
    'id' => 'cdexamcontrol-notifications',
    'class' => 'btn btn-outline-secondary',
]);
if (has_capability('quizaccess/cdexamcontrol:exportreport', $context)) {
    echo html_writer::link($summaryexporturl, get_string('exportsummarycsv', 'quizaccess_cdexamcontrol'), [
        'class' => 'btn btn-primary',
    ]);
    echo html_writer::link($incidentexporturl, get_string('exportincidentscsv', 'quizaccess_cdexamcontrol'), [
        'class' => 'btn btn-outline-primary',
    ]);
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div('', 'alert alert-danger d-none', [
    'id' => 'cdexamcontrol-error',
    'role' => 'alert',
]);

$cards = [
    'active' => 'activeattempts',
    'attention' => 'attentionnow',
    'review' => 'reviewpriority',
    'connected' => 'connectedattempts',
    'incidents' => 'totalincidents',
];
echo html_writer::start_div('cdexamcontrol-summary');
foreach ($cards as $id => $stringkey) {
    echo html_writer::start_div('cdexamcontrol-card cdexamcontrol-card-' . $id);
    echo html_writer::tag('span', '0', [
        'id' => 'cdexamcontrol-count-' . $id,
        'class' => 'cdexamcontrol-card-value',
    ]);
    echo html_writer::tag('span', get_string($stringkey, 'quizaccess_cdexamcontrol'), [
        'class' => 'cdexamcontrol-card-label',
    ]);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::tag('h3', get_string('participants', 'quizaccess_cdexamcontrol'), ['class' => 'mt-4']);
echo html_writer::start_div('cdexamcontrol-filters');
echo html_writer::tag('label', get_string('searchattempts', 'quizaccess_cdexamcontrol'), [
    'for' => 'cdexamcontrol-search',
    'class' => 'sr-only',
]);
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'id' => 'cdexamcontrol-search',
    'class' => 'form-control',
    'placeholder' => get_string('searchattemptsplaceholder', 'quizaccess_cdexamcontrol'),
]);
echo html_writer::tag('label', get_string('filterlabel', 'quizaccess_cdexamcontrol'), [
    'for' => 'cdexamcontrol-filter',
    'class' => 'sr-only',
]);
echo html_writer::select([
    'all' => get_string('filterall', 'quizaccess_cdexamcontrol'),
    'review' => get_string('filterreview', 'quizaccess_cdexamcontrol'),
    'attention' => get_string('filterattention', 'quizaccess_cdexamcontrol'),
    'disconnected' => get_string('filterdisconnected', 'quizaccess_cdexamcontrol'),
], 'cdexamcontrol-filter', 'all', false, [
    'id' => 'cdexamcontrol-filter',
    'class' => 'custom-select',
]);
echo html_writer::end_div();
echo html_writer::start_div('table-responsive');
echo html_writer::start_tag('table', ['class' => 'table table-striped cdexamcontrol-table']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach (
    [
        'student',
        'attempt',
        'connection',
        'focusstate',
        'reviewpriority',
        'incidentcount',
        'totaltimeaway',
        'lastheartbeat',
    ] as $key
) {
    echo html_writer::tag('th', get_string($key, 'quizaccess_cdexamcontrol'), ['scope' => 'col']);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::tag('tbody', '', ['id' => 'cdexamcontrol-participants-body']);
echo html_writer::end_tag('table');
echo html_writer::end_div();

echo html_writer::tag('h3', get_string('recentincidents', 'quizaccess_cdexamcontrol'), ['class' => 'mt-4']);
echo html_writer::start_div('table-responsive');
echo html_writer::start_tag('table', ['class' => 'table table-sm table-hover cdexamcontrol-table']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
foreach (['student', 'attempt', 'started', 'ended', 'duration', 'reason'] as $key) {
    echo html_writer::tag('th', get_string($key, 'quizaccess_cdexamcontrol'), ['scope' => 'col']);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::tag('tbody', '', ['id' => 'cdexamcontrol-incidents-body']);
echo html_writer::end_tag('table');
echo html_writer::end_div();

echo html_writer::div(get_string('reviewdisclaimer', 'quizaccess_cdexamcontrol'), 'alert alert-info mt-4');
echo html_writer::div(get_string('privacywarning', 'quizaccess_cdexamcontrol'), 'alert alert-light mt-4');
echo html_writer::tag('noscript', get_string('noscript', 'quizaccess_cdexamcontrol'), ['class' => 'alert alert-warning']);
echo html_writer::end_div();
echo $OUTPUT->footer();
