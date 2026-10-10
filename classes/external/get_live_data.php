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

namespace quizaccess_cdexamcontrol\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use quizaccess_cdexamcontrol\local\report_service;

/**
 * External function that supplies the teacher's live report.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_live_data extends external_api {
    /**
     * Describe report request fields.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Quiz course-module ID'),
            'groupid' => new external_value(PARAM_INT, 'Selected group ID', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Return a permission-aware live report snapshot.
     *
     * @param int $cmid Quiz course-module ID.
     * @param int $groupid Selected group ID.
     * @return array
     */
    public static function execute(int $cmid, int $groupid = 0): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'groupid' => $groupid,
        ]);
        $cm = get_coursemodule_from_id('quiz', $params['cmid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        require_login($course, false, $cm);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('quizaccess/cdexamcontrol:viewreport', $context);

        return report_service::get_live_data($cm, $params['groupid']);
    }

    /**
     * Describe the complete live report payload.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'servertime' => new external_value(PARAM_INT, 'Server Unix timestamp'),
            'serverTimeText' => new external_value(PARAM_TEXT, 'Formatted server time'),
            'summary' => new external_single_structure([
                'activeAttempts' => new external_value(PARAM_INT, 'In-progress attempts'),
                'attentionNow' => new external_value(PARAM_INT, 'Attempts with focus currently lost'),
                'connectedAttempts' => new external_value(PARAM_INT, 'Connected attempts'),
                'needsReview' => new external_value(PARAM_INT, 'Attempts meeting review thresholds'),
                'totalIncidents' => new external_value(PARAM_INT, 'Visible incident count'),
            ]),
            'participants' => new external_multiple_structure(new external_single_structure([
                'attemptid' => new external_value(PARAM_INT, 'Attempt ID'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Student full name'),
                'attempt' => new external_value(PARAM_INT, 'Attempt number'),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Connection status'),
                'statustext' => new external_value(PARAM_TEXT, 'Formatted connection status'),
                'focuslost' => new external_value(PARAM_BOOL, 'Whether focus is currently lost'),
                'focustext' => new external_value(PARAM_TEXT, 'Formatted focus status'),
                'incidentcount' => new external_value(PARAM_INT, 'Incident count'),
                'totalduration' => new external_value(PARAM_INT, 'Total duration in seconds'),
                'totaldurationtext' => new external_value(PARAM_TEXT, 'Formatted total duration'),
                'lastheartbeat' => new external_value(PARAM_INT, 'Last heartbeat timestamp'),
                'lastheartbeattext' => new external_value(PARAM_TEXT, 'Formatted last heartbeat'),
                'attemptstarted' => new external_value(PARAM_INT, 'Attempt start timestamp'),
                'attemptstartedtext' => new external_value(PARAM_TEXT, 'Formatted attempt start'),
                'needsreview' => new external_value(PARAM_BOOL, 'Whether review thresholds are met'),
                'reviewtext' => new external_value(PARAM_TEXT, 'Formatted review priority'),
            ])),
            'incidents' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Incident ID'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Student full name'),
                'attempt' => new external_value(PARAM_INT, 'Attempt number'),
                'reason' => new external_value(PARAM_ALPHANUMEXT, 'Detection reason'),
                'reasontext' => new external_value(PARAM_TEXT, 'Formatted detection reason'),
                'started' => new external_value(PARAM_INT, 'Incident start timestamp'),
                'startedtext' => new external_value(PARAM_TEXT, 'Formatted incident start'),
                'ended' => new external_value(PARAM_INT, 'Incident end timestamp'),
                'endedtext' => new external_value(PARAM_TEXT, 'Formatted incident end'),
                'duration' => new external_value(PARAM_INT, 'Incident duration in seconds'),
                'durationtext' => new external_value(PARAM_TEXT, 'Formatted incident duration'),
                'active' => new external_value(PARAM_BOOL, 'Whether the incident is active'),
            ])),
        ]);
    }
}
