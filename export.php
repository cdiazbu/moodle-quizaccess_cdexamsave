<?php
// This file is part of Moodle - http://moodle.org/

/**
 * CSV export of all visible focus-loss incidents for a quiz.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno <carlosdiazbueno@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Neutralise values that spreadsheet applications could interpret as a
 * formula when the CSV is opened.
 *
 * @param string $value Export value.
 * @return string
 */
function quizaccess_cdexamsave_csv_safe(string $value): string {
    return preg_match('/^[=+\-@\t\r]/u', $value) ? "'" . $value : $value;
}

$cmid = required_param('cmid', PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('quizaccess/cdexamsave:exportreport', $context);

$rows = \quizaccess_cdexamsave\local\report_service::get_export_rows($cm, $groupid);
$export = new csv_export_writer();
$export->set_filename(clean_filename('CDexamSave-' . format_string($quiz->name) . '-' . userdate(time(), '%Y%m%d-%H%M')));
$export->add_data([
    get_string('export_student', 'quizaccess_cdexamsave'),
    get_string('export_userid', 'quizaccess_cdexamsave'),
    get_string('export_attempt', 'quizaccess_cdexamsave'),
    get_string('export_started', 'quizaccess_cdexamsave'),
    get_string('export_returned', 'quizaccess_cdexamsave'),
    get_string('export_duration', 'quizaccess_cdexamsave'),
    get_string('export_reason', 'quizaccess_cdexamsave'),
    get_string('export_active', 'quizaccess_cdexamsave'),
]);

$now = time();
foreach ($rows as $row) {
    $active = empty($row->timeend);
    $duration = $active ? max(0, $now - (int) $row->timestart) : (int) $row->duration;
    $export->add_data([
        quizaccess_cdexamsave_csv_safe(fullname($row)),
        (int) $row->userid,
        (int) $row->attempt,
        userdate((int) $row->timestart),
        $active ? '' : userdate((int) $row->timeend),
        $duration,
        get_string('reason_' . $row->reason, 'quizaccess_cdexamsave'),
        get_string($active ? 'yes' : 'no', 'quizaccess_cdexamsave'),
    ]);
}
$export->download_file();
