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

namespace quizaccess_cdexamsave\local;

/**
 * Builds permission-aware live and export datasets.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_service {
    /**
     * Build the live report payload.
     *
     * @param \stdClass $cm Quiz course-module record.
     * @param int $groupid Selected group, or zero.
     * @return array
     */
    public static function get_live_data(\stdClass $cm, int $groupid = 0): array {
        global $DB;

        $context = \context_module::instance($cm->id);
        require_capability('quizaccess/cdexamsave:viewreport', $context);
        $alloweduserids = self::get_allowed_userids($cm, $context, $groupid);
        $now = time();

        [$userwhere, $userparams] = self::user_filter_sql('qa.userid', $alloweduserids, 'activeuser');
        $params = array_merge([
            'quizid' => $cm->instance,
            'inprogress' => 'inprogress',
        ], $userparams);
        $sql = "SELECT qa.id AS attemptid, qa.userid, qa.attempt, qa.timestart,
                       u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename
                  FROM {quiz_attempts} qa
                  JOIN {user} u ON u.id = qa.userid
                 WHERE qa.quiz = :quizid
                       AND qa.preview = 0
                       AND qa.state = :inprogress
                       {$userwhere}
              ORDER BY u.lastname ASC, u.firstname ASC, qa.attempt ASC";
        $attempts = $DB->get_records_sql($sql, $params);

        $attemptids = array_map('intval', array_keys($attempts));
        $sessions = [];
        $aggregates = [];
        if ($attemptids) {
            [$attemptsql, $attemptparams] = $DB->get_in_or_equal(
                $attemptids,
                SQL_PARAMS_NAMED,
                'attempt'
            );
            $sessionrecords = $DB->get_records_select(
                'quizaccess_cdexamsave_sess',
                "attemptid {$attemptsql}",
                $attemptparams
            );
            foreach ($sessionrecords as $session) {
                $sessions[(int) $session->attemptid] = $session;
            }

            $aggregatesql = "SELECT attemptid, COUNT(id) AS incidentcount,
                                    SUM(duration) AS totalduration,
                                    MAX(timestart) AS lastincident
                               FROM {quizaccess_cdexamsave_evt}
                              WHERE attemptid {$attemptsql}
                           GROUP BY attemptid";
            foreach ($DB->get_records_sql($aggregatesql, $attemptparams) as $aggregate) {
                $aggregates[(int) $aggregate->attemptid] = $aggregate;
            }
        }

        $heartbeatinterval = (int) get_config('quizaccess_cdexamsave', 'heartbeatinterval');
        $heartbeatinterval = max(5, min(60, $heartbeatinterval ?: 10));
        $staleseconds = (int) get_config('quizaccess_cdexamsave', 'staleseconds');
        $staleseconds = max(15, min(300, $staleseconds ?: max(35, $heartbeatinterval * 3)));

        $participantrows = [];
        $attentioncount = 0;
        $connectedcount = 0;
        foreach ($attempts as $attempt) {
            $attemptid = (int) $attempt->attemptid;
            $session = $sessions[$attemptid] ?? null;
            $aggregate = $aggregates[$attemptid] ?? null;
            $focuslost = $session && !empty($session->focuslost);
            $lastheartbeat = $session ? (int) $session->lastheartbeat : 0;

            if ($focuslost) {
                $status = 'attention';
                $attentioncount++;
            } else if (!$session) {
                $status = 'notstarted';
            } else if (($now - $lastheartbeat) > $staleseconds) {
                $status = 'disconnected';
            } else {
                $status = 'connected';
                $connectedcount++;
            }

            $totalduration = $aggregate ? (int) $aggregate->totalduration : 0;
            if ($focuslost && !empty($session->lostsince)) {
                $totalduration += max(0, $now - (int) $session->lostsince);
            }
            $participantrows[] = [
                'attemptid' => $attemptid,
                'userid' => (int) $attempt->userid,
                'fullname' => fullname($attempt),
                'attempt' => (int) $attempt->attempt,
                'status' => $status,
                'statustext' => get_string('status_' . $status, 'quizaccess_cdexamsave'),
                'focuslost' => (bool) $focuslost,
                'focustext' => get_string($focuslost ? 'focus_lost' : 'focus_ok', 'quizaccess_cdexamsave'),
                'incidentcount' => $aggregate ? (int) $aggregate->incidentcount : 0,
                'totalduration' => $totalduration,
                'totaldurationtext' => format_time($totalduration),
                'lastheartbeat' => $lastheartbeat,
                'lastheartbeattext' => $lastheartbeat ? userdate($lastheartbeat, get_string('strftimetime', 'langconfig')) : '—',
                'attemptstarted' => (int) $attempt->timestart,
                'attemptstartedtext' => userdate((int) $attempt->timestart),
            ];
        }

        usort($participantrows, static function (array $left, array $right): int {
            $weights = ['attention' => 0, 'disconnected' => 1, 'notstarted' => 2, 'connected' => 3];
            $comparison = $weights[$left['status']] <=> $weights[$right['status']];
            return $comparison ?: strcasecmp($left['fullname'], $right['fullname']);
        });

        $recentincidents = self::get_recent_incidents($cm, $alloweduserids, $now);
        $totalincidents = self::count_incidents($cm, $alloweduserids);

        return [
            'servertime' => $now,
            'serverTimeText' => userdate($now, get_string('strftimetime', 'langconfig')),
            'summary' => [
                'activeAttempts' => count($participantrows),
                'attentionNow' => $attentioncount,
                'connectedAttempts' => $connectedcount,
                'totalIncidents' => $totalincidents,
            ],
            'participants' => $participantrows,
            'incidents' => $recentincidents,
        ];
    }

    /**
     * Get all incident rows for CSV export.
     *
     * @param \stdClass $cm Quiz course-module record.
     * @param int $groupid Selected group, or zero.
     * @return array
     */
    public static function get_export_rows(\stdClass $cm, int $groupid = 0): array {
        global $DB;

        $context = \context_module::instance($cm->id);
        require_capability('quizaccess/cdexamsave:exportreport', $context);
        $alloweduserids = self::get_allowed_userids($cm, $context, $groupid);
        [$userwhere, $userparams] = self::user_filter_sql('e.userid', $alloweduserids, 'exportuser');
        $params = array_merge(['quizid' => $cm->instance], $userparams);
        $sql = "SELECT e.id, e.userid, e.attemptid, e.reason, e.timestart, e.timeend,
                       e.duration, qa.attempt, u.firstname, u.lastname, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {quizaccess_cdexamsave_evt} e
                  JOIN {quiz_attempts} qa ON qa.id = e.attemptid
                  JOIN {user} u ON u.id = e.userid
                 WHERE e.quizid = :quizid {$userwhere}
              ORDER BY e.timestart ASC, e.id ASC";
        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Return recent incident rows.
     *
     * @param \stdClass $cm Course module.
     * @param array|null $alloweduserids Null means all users.
     * @param int $now Current timestamp.
     * @return array
     */
    private static function get_recent_incidents(\stdClass $cm, ?array $alloweduserids, int $now): array {
        global $DB;

        [$userwhere, $userparams] = self::user_filter_sql('e.userid', $alloweduserids, 'recentuser');
        $params = array_merge(['quizid' => $cm->instance], $userparams);
        $sql = "SELECT e.id, e.userid, e.attemptid, e.reason, e.timestart, e.timeend,
                       e.duration, qa.attempt, u.firstname, u.lastname, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {quizaccess_cdexamsave_evt} e
                  JOIN {quiz_attempts} qa ON qa.id = e.attemptid
                  JOIN {user} u ON u.id = e.userid
                 WHERE e.quizid = :quizid {$userwhere}
              ORDER BY e.timestart DESC, e.id DESC";
        $records = $DB->get_records_sql($sql, $params, 0, 200);

        $rows = [];
        foreach ($records as $record) {
            $active = empty($record->timeend);
            $duration = $active ? max(0, $now - (int) $record->timestart) : (int) $record->duration;
            $rows[] = [
                'id' => (int) $record->id,
                'userid' => (int) $record->userid,
                'fullname' => fullname($record),
                'attempt' => (int) $record->attempt,
                'reason' => $record->reason,
                'reasontext' => get_string('reason_' . $record->reason, 'quizaccess_cdexamsave'),
                'started' => (int) $record->timestart,
                'startedtext' => userdate((int) $record->timestart),
                'ended' => (int) $record->timeend,
                'endedtext' => $active ? get_string('incidentactive', 'quizaccess_cdexamsave') : userdate((int) $record->timeend),
                'duration' => $duration,
                'durationtext' => format_time($duration),
                'active' => $active,
            ];
        }
        return $rows;
    }

    /**
     * Count all incidents visible under the current group restriction.
     *
     * @param \stdClass $cm Course module.
     * @param array|null $alloweduserids Null means all users.
     * @return int
     */
    private static function count_incidents(\stdClass $cm, ?array $alloweduserids): int {
        global $DB;

        [$userwhere, $userparams] = self::user_filter_sql('userid', $alloweduserids, 'countuser');
        $params = array_merge(['quizid' => $cm->instance], $userparams);
        return (int) $DB->count_records_select(
            'quizaccess_cdexamsave_evt',
            "quizid = :quizid {$userwhere}",
            $params
        );
    }

    /**
     * Resolve group restrictions for the current teacher.
     *
     * Null means no user filter. An empty array means no visible students.
     *
     * @param \stdClass $cm Course module.
     * @param \context_module $context Activity context.
     * @param int $groupid Requested group.
     * @return array|null
     */
    private static function get_allowed_userids(
        \stdClass $cm,
        \context_module $context,
        int $groupid
    ): ?array {
        global $DB, $USER;

        $groupmode = groups_get_activity_groupmode($cm);
        $canaccessallgroups = has_capability('moodle/site:accessallgroups', $context);

        if ($groupid > 0) {
            $group = groups_get_group($groupid, 'id,courseid', MUST_EXIST);
            if (
                (int) $group->courseid !== (int) $cm->course ||
                (
                    $groupmode == SEPARATEGROUPS &&
                    !$canaccessallgroups &&
                    !groups_is_member($groupid, $USER->id)
                )
            ) {
                throw new \moodle_exception('invalidgroup', 'quizaccess_cdexamsave');
            }
            return array_map('intval', array_keys(groups_get_members($groupid, 'u.id')));
        }

        if ($groupmode != SEPARATEGROUPS || $canaccessallgroups) {
            return null;
        }

        $groups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid, 'g.id');
        if (!$groups) {
            return [];
        }
        [$groupsql, $groupparams] = $DB->get_in_or_equal(
            array_map('intval', array_keys($groups)),
            SQL_PARAMS_NAMED,
            'allowedgroup'
        );
        return array_map('intval', $DB->get_fieldset_select(
            'groups_members',
            'DISTINCT userid',
            "groupid {$groupsql}",
            $groupparams
        ));
    }

    /**
     * Build a portable user IN clause.
     *
     * @param string $field SQL field.
     * @param array|null $userids Null for no filter.
     * @param string $prefix Named parameter prefix.
     * @return array SQL fragment and parameters.
     */
    private static function user_filter_sql(string $field, ?array $userids, string $prefix): array {
        global $DB;

        if ($userids === null) {
            return ['', []];
        }
        if (!$userids) {
            return ['AND 1 = 0', []];
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, $prefix);
        return ["AND {$field} {$insql}", $params];
    }
}
