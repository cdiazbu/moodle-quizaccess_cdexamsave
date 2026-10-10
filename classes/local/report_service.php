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

namespace quizaccess_cdexamcontrol\local;

/**
 * Builds permission-aware live and export datasets.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_service {
    /**
     * Return bounded site-wide review thresholds.
     *
     * @return array Incident-count and cumulative-duration thresholds.
     */
    public static function get_review_thresholds(): array {
        $incidentcount = (int) get_config('quizaccess_cdexamcontrol', 'reviewincidentcount');
        $duration = (int) get_config('quizaccess_cdexamcontrol', 'reviewduration');

        return [
            'incidentcount' => max(1, min(100, $incidentcount ?: 3)),
            'duration' => max(1, min(DAYSECS, $duration ?: 60)),
        ];
    }

    /**
     * Decide whether an attempt should be prioritised for human review.
     *
     * This is a workflow aid only. It does not classify misconduct.
     *
     * @param int $incidentcount Number of incidents.
     * @param int $totalduration Cumulative duration in seconds.
     * @param bool $focuslost Whether focus is currently lost.
     * @param array|null $thresholds Optional thresholds for deterministic tests.
     * @return bool
     */
    public static function needs_review(
        int $incidentcount,
        int $totalduration,
        bool $focuslost = false,
        ?array $thresholds = null
    ): bool {
        $thresholds = $thresholds ?? self::get_review_thresholds();
        return $focuslost ||
            $incidentcount >= (int) $thresholds['incidentcount'] ||
            $totalduration >= (int) $thresholds['duration'];
    }

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
        require_capability('quizaccess/cdexamcontrol:viewreport', $context);
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
                'quizaccess_cdexamcontrol_ses',
                "attemptid {$attemptsql}",
                $attemptparams
            );
            foreach ($sessionrecords as $session) {
                $sessions[(int) $session->attemptid] = $session;
            }

            $aggregatesql = "SELECT attemptid, COUNT(id) AS incidentcount,
                                    SUM(duration) AS totalduration,
                                    MAX(timestart) AS lastincident
                               FROM {quizaccess_cdexamcontrol_evt}
                              WHERE attemptid {$attemptsql}
                           GROUP BY attemptid";
            foreach ($DB->get_records_sql($aggregatesql, $attemptparams) as $aggregate) {
                $aggregates[(int) $aggregate->attemptid] = $aggregate;
            }
        }

        $heartbeatinterval = (int) get_config('quizaccess_cdexamcontrol', 'heartbeatinterval');
        $heartbeatinterval = max(5, min(60, $heartbeatinterval ?: 10));
        $staleseconds = (int) get_config('quizaccess_cdexamcontrol', 'staleseconds');
        $staleseconds = max(15, min(300, $staleseconds ?: max(35, $heartbeatinterval * 3)));

        $participantrows = [];
        $attentioncount = 0;
        $connectedcount = 0;
        $reviewcount = 0;
        $reviewthresholds = self::get_review_thresholds();
        foreach ($attempts as $attempt) {
            $attemptid = (int) $attempt->attemptid;
            $session = $sessions[$attemptid] ?? null;
            $aggregate = $aggregates[$attemptid] ?? null;
            $focuslost = $session && !empty($session->focuslost);
            $lastheartbeat = $session ? (int) $session->lastheartbeat : 0;

            $exempt = has_capability('quizaccess/cdexamcontrol:exempt', $context, $attempt->userid);
            if ($exempt) {
                $status = 'exempt';
                $focuslost = false;
            } else if ($focuslost) {
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
            $incidentcount = $aggregate ? (int) $aggregate->incidentcount : 0;
            $needsreview = self::needs_review(
                $incidentcount,
                $totalduration,
                (bool) $focuslost,
                $reviewthresholds
            );
            $needsreview = $needsreview && !$exempt;
            if ($needsreview) {
                $reviewcount++;
            }
            $participantrows[] = [
                'attemptid' => $attemptid,
                'userid' => (int) $attempt->userid,
                'fullname' => fullname($attempt),
                'attempt' => (int) $attempt->attempt,
                'status' => $status,
                'statustext' => get_string('status_' . $status, 'quizaccess_cdexamcontrol'),
                'focuslost' => (bool) $focuslost,
                'focustext' => get_string($focuslost ? 'focus_lost' : 'focus_ok', 'quizaccess_cdexamcontrol'),
                'incidentcount' => $incidentcount,
                'totalduration' => $totalduration,
                'totaldurationtext' => format_time($totalduration),
                'lastheartbeat' => $lastheartbeat,
                'lastheartbeattext' => $lastheartbeat ? userdate($lastheartbeat, get_string('strftimetime', 'langconfig')) : '—',
                'attemptstarted' => (int) $attempt->timestart,
                'attemptstartedtext' => userdate((int) $attempt->timestart),
                'needsreview' => $needsreview,
                'reviewtext' => get_string($needsreview ? 'reviewrecommended' : 'reviewnotneeded', 'quizaccess_cdexamcontrol'),
            ];
        }

        usort($participantrows, static function (array $left, array $right): int {
            $weights = ['attention' => 0, 'disconnected' => 2, 'notstarted' => 3, 'connected' => 4, 'exempt' => 5];
            $leftweight = $left['needsreview'] && $left['status'] !== 'attention' ? 1 : $weights[$left['status']];
            $rightweight = $right['needsreview'] && $right['status'] !== 'attention' ? 1 : $weights[$right['status']];
            $comparison = $leftweight <=> $rightweight;
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
                'needsReview' => $reviewcount,
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
        require_capability('quizaccess/cdexamcontrol:exportreport', $context);
        $alloweduserids = self::get_allowed_userids($cm, $context, $groupid);
        [$userwhere, $userparams] = self::user_filter_sql('e.userid', $alloweduserids, 'exportuser');
        $params = array_merge(['quizid' => $cm->instance], $userparams);
        $sql = "SELECT e.id, e.userid, e.attemptid, e.reason, e.timestart, e.timeend,
                       e.duration, qa.attempt, u.firstname, u.lastname, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {quizaccess_cdexamcontrol_evt} e
                  JOIN {quiz_attempts} qa ON qa.id = e.attemptid
                  JOIN {user} u ON u.id = e.userid
                 WHERE e.quizid = :quizid {$userwhere}
              ORDER BY e.timestart ASC, e.id ASC";
        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Get one export row per non-preview attempt, including attempts without
     * focus-loss incidents.
     *
     * @param \stdClass $cm Quiz course-module record.
     * @param int $groupid Selected group, or zero.
     * @return array
     */
    public static function get_attempt_summary_rows(\stdClass $cm, int $groupid = 0): array {
        global $DB;

        $context = \context_module::instance($cm->id);
        require_capability('quizaccess/cdexamcontrol:exportreport', $context);
        $alloweduserids = self::get_allowed_userids($cm, $context, $groupid);
        [$userwhere, $userparams] = self::user_filter_sql('qa.userid', $alloweduserids, 'summaryuser');
        $params = array_merge([
            'quizid' => $cm->instance,
            'nowtotals' => time(),
            'nowmaximum' => time(),
        ], $userparams);
        $sql = "SELECT qa.id AS attemptid, qa.userid, qa.attempt, qa.state,
                       qa.timestart, qa.timefinish,
                       u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename,
                       COALESCE(e.incidentcount, 0) AS incidentcount,
                       COALESCE(e.totalduration, 0) AS totalduration,
                       COALESCE(e.maxduration, 0) AS maxduration
                  FROM {quiz_attempts} qa
                  JOIN {user} u ON u.id = qa.userid
             LEFT JOIN (
                           SELECT attemptid, COUNT(id) AS incidentcount,
                                  SUM(CASE WHEN timeend = 0 THEN :nowtotals - timestart ELSE duration END)
                                      AS totalduration,
                                  MAX(CASE WHEN timeend = 0 THEN :nowmaximum - timestart ELSE duration END)
                                      AS maxduration
                             FROM {quizaccess_cdexamcontrol_evt}
                         GROUP BY attemptid
                       ) e ON e.attemptid = qa.id
                 WHERE qa.quiz = :quizid
                       AND qa.preview = 0
                       {$userwhere}
              ORDER BY u.lastname ASC, u.firstname ASC, qa.attempt ASC";

        $thresholds = self::get_review_thresholds();
        $rows = array_values($DB->get_records_sql($sql, $params));
        foreach ($rows as $row) {
            $row->incidentcount = (int) $row->incidentcount;
            $row->totalduration = max(0, (int) $row->totalduration);
            $row->maxduration = max(0, (int) $row->maxduration);
            $row->needsreview = self::needs_review(
                $row->incidentcount,
                $row->totalduration,
                false,
                $thresholds
            );
        }
        return $rows;
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
                  FROM {quizaccess_cdexamcontrol_evt} e
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
                'reasontext' => get_string('reason_' . $record->reason, 'quizaccess_cdexamcontrol'),
                'started' => (int) $record->timestart,
                'startedtext' => userdate((int) $record->timestart),
                'ended' => (int) $record->timeend,
                'endedtext' => $active ? get_string('incidentactive', 'quizaccess_cdexamcontrol') :
                    userdate((int) $record->timeend),
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
            'quizaccess_cdexamcontrol_evt',
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

        if ($groupid < 0) {
            throw new \moodle_exception('invalidgroup', 'quizaccess_cdexamcontrol');
        }
        $groupmode = groups_get_activity_groupmode($cm);
        $canaccessallgroups = has_capability('moodle/site:accessallgroups', $context);

        if ($groupid > 0) {
            $group = groups_get_group($groupid, 'id,courseid', MUST_EXIST);
            if (
                (int) $group->courseid !== (int) $cm->course ||
                (!empty($cm->groupingid) && !$DB->record_exists('groupings_groups', [
                    'groupingid' => $cm->groupingid,
                    'groupid' => $groupid,
                ])) ||
                (
                    $groupmode == SEPARATEGROUPS &&
                    !$canaccessallgroups &&
                    !groups_is_member($groupid, $USER->id)
                )
            ) {
                throw new \moodle_exception('invalidgroup', 'quizaccess_cdexamcontrol');
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
