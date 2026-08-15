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
 * CSV exports for visible focus-loss data in a quiz.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
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
$mode = optional_param('mode', 'incidents', PARAM_ALPHA);
if (!in_array($mode, ['incidents', 'summary'], true)) {
    throw new moodle_exception('invalidrequest', 'quizaccess_cdexamsave');
}
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('quizaccess/cdexamsave:exportreport', $context);

$export = new csv_export_writer();
$export->set_filename(clean_filename(
    'CD ExamFocus-' . $mode . '-' . format_string($quiz->name) . '-' . userdate(time(), '%Y%m%d-%H%M')
));

if ($mode === 'summary') {
    $rows = \quizaccess_cdexamsave\local\report_service::get_attempt_summary_rows($cm, $groupid);
    $export->add_data([
        get_string('export_student', 'quizaccess_cdexamsave'),
        get_string('export_userid', 'quizaccess_cdexamsave'),
        get_string('export_attempt', 'quizaccess_cdexamsave'),
        get_string('export_state', 'quizaccess_cdexamsave'),
        get_string('export_start', 'quizaccess_cdexamsave'),
        get_string('export_finish', 'quizaccess_cdexamsave'),
        get_string('export_incidentcount', 'quizaccess_cdexamsave'),
        get_string('export_totalduration', 'quizaccess_cdexamsave'),
        get_string('export_maxduration', 'quizaccess_cdexamsave'),
        get_string('export_needsreview', 'quizaccess_cdexamsave'),
    ]);
    foreach ($rows as $row) {
        $export->add_data([
            quizaccess_cdexamsave_csv_safe(fullname($row)),
            (int) $row->userid,
            (int) $row->attempt,
            get_string('state' . $row->state, 'quiz'),
            userdate((int) $row->timestart),
            empty($row->timefinish) ? '' : userdate((int) $row->timefinish),
            (int) $row->incidentcount,
            (int) $row->totalduration,
            (int) $row->maxduration,
            get_string($row->needsreview ? 'reviewrecommended' : 'reviewnotneeded', 'quizaccess_cdexamsave'),
        ]);
    }
} else {
    $rows = \quizaccess_cdexamsave\local\report_service::get_export_rows($cm, $groupid);
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
}
$export->download_file();
