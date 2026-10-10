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
use core_external\external_single_structure;
use core_external\external_value;
use quizaccess_cdexamcontrol\local\incident_service;

/**
 * External function used by the student monitor to record one signal.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class record_signal extends external_api {
    /**
     * Describe accepted signal fields.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Quiz attempt ID'),
            'cmid' => new external_value(PARAM_INT, 'Quiz course-module ID'),
            'pagesessionid' => new external_value(PARAM_RAW_TRIMMED, 'Browser page-session UUID'),
            'action' => new external_value(PARAM_ALPHA, 'Signal action'),
            'eventuuid' => new external_value(PARAM_RAW_TRIMMED, 'Incident UUID', VALUE_DEFAULT, ''),
            'reason' => new external_value(PARAM_ALPHANUMEXT, 'Browser detection reason', VALUE_DEFAULT, 'unknown'),
            'clienttime' => new external_value(PARAM_INT, 'Client Unix timestamp', VALUE_DEFAULT, 0),
            'duration' => new external_value(PARAM_INT, 'Client incident duration in seconds', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Record a focus-monitoring signal.
     *
     * @param int $attemptid Quiz attempt ID.
     * @param int $cmid Quiz course-module ID.
     * @param string $pagesessionid Browser page-session UUID.
     * @param string $action Signal action.
     * @param string $eventuuid Incident UUID.
     * @param string $reason Detection reason.
     * @param int $clienttime Client Unix timestamp.
     * @param int $duration Client duration in seconds.
     * @return array
     */
    public static function execute(
        int $attemptid,
        int $cmid,
        string $pagesessionid,
        string $action,
        string $eventuuid = '',
        string $reason = 'unknown',
        int $clienttime = 0,
        int $duration = 0
    ): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'attemptid' => $attemptid,
            'cmid' => $cmid,
            'pagesessionid' => $pagesessionid,
            'action' => $action,
            'eventuuid' => $eventuuid,
            'reason' => $reason,
            'clienttime' => $clienttime,
            'duration' => $duration,
        ]);

        $cm = get_coursemodule_from_id('quiz', $params['cmid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        require_login($course, false, $cm);
        self::validate_context(\context_module::instance($cm->id));

        $attempt = $DB->get_record('quiz_attempts', ['id' => $params['attemptid']], 'id,quiz', MUST_EXIST);
        if ((int) $attempt->quiz !== (int) $cm->instance) {
            throw new \moodle_exception('invalidrequest', 'quizaccess_cdexamcontrol');
        }

        return incident_service::record($params);
    }

    /**
     * Describe the collector response.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'accepted' => new external_value(PARAM_BOOL, 'Whether the signal was accepted'),
            'servertime' => new external_value(PARAM_INT, 'Server Unix timestamp'),
        ]);
    }
}
