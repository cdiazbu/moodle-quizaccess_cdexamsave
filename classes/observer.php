<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_cdexamsave;

/**
 * Quiz event observers.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno <carlosdiazbueno@gmail.com>
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
