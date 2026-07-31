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

namespace quizaccess_cdexamsave;

/**
 * Quiz event observers.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Mark monitoring inactive and close any open incident.
     *
     * @param \core\event\base $event Quiz attempt event.
     * @return void
     */
    public static function attempt_finished(\core\event\base $event): void {
        global $DB;

        $attemptid = (int) $event->objectid;
        $now = time();
        \quizaccess_cdexamsave\local\incident_service::close_open_incidents($attemptid, $now);
        $session = $DB->get_record('quizaccess_cdexamsave_sess', ['attemptid' => $attemptid]);
        if ($session) {
            $session->active = 0;
            $session->focuslost = 0;
            $session->lostsince = 0;
            $session->timemodified = $now;
            $DB->update_record('quizaccess_cdexamsave_sess', $session);
        }
    }

    /**
     * Remove monitoring data when its quiz attempt is deleted.
     *
     * @param \core\event\base $event Attempt-deleted event.
     * @return void
     */
    public static function attempt_deleted(\core\event\base $event): void {
        global $DB;

        $attemptid = (int) $event->objectid;
        $DB->delete_records('quizaccess_cdexamsave_evt', ['attemptid' => $attemptid]);
        $DB->delete_records('quizaccess_cdexamsave_sess', ['attemptid' => $attemptid]);
    }
}
