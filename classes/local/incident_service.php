<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_cdexamsave\local;

/**
 * Validates and records browser monitoring signals.
 *
 * All write operations are idempotent. Browser events can be retried after a
 * transient network failure without creating duplicate incidents.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno <carlosdiazbueno@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class incident_service {
    /** @var array Permitted browser event reasons. */
    private const REASONS = [
        'visibility_hidden',
        'window_blur',
        'pagehide',
        'freeze',
        'unknown',
    ];

    /** @var array Permitted collector actions. */
    private const ACTIONS = ['init', 'heartbeat', 'lost', 'returned'];

    /**
     * Record one browser signal.
     *
     * @param array $payload Decoded request body.
     * @return array Collector response.
     */
    public static function record(array $payload): array {
        global $DB, $USER;

        $attemptid = clean_param($payload['attemptid'] ?? 0, PARAM_INT);
        $action = clean_param($payload['action'] ?? '', PARAM_ALPHA);
        $pagesessionid = strtolower(clean_param($payload['pagesessionid'] ?? '', PARAM_RAW_TRIMMED));
        $eventuuid = strtolower(clean_param($payload['eventuuid'] ?? '', PARAM_RAW_TRIMMED));
        $reason = self::normalise_reason($payload['reason'] ?? 'unknown');
        $clienttime = max(0, (int) ($payload['clienttime'] ?? 0));
        $duration = max(0, min(WEEKSECS, (int) ($payload['duration'] ?? 0)));

        if (!$attemptid || !in_array($action, self::ACTIONS, true) || !self::is_valid_uuid($pagesessionid)) {
            throw new \moodle_exception('invalidrequest', 'quizaccess_cdexamsave');
        }
        if (in_array($action, ['lost', 'returned'], true) && !self::is_valid_uuid($eventuuid)) {
            throw new \moodle_exception('invalidrequest', 'quizaccess_cdexamsave');
        }

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
        $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
        require_login($course, false, $cm);

        if ((int) $attempt->userid !== (int) $USER->id || !empty($attempt->preview) ||
                $attempt->state !== 'inprogress') {
            throw new \moodle_exception('attemptnotmonitorable', 'quizaccess_cdexamsave');
        }
        $duration = min($duration, max(0, time() - (int) $attempt->timestart));
        if (!$DB->record_exists('quizaccess_cdexamsave', ['quizid' => $quiz->id, 'enabled' => 1])) {
            throw new \moodle_exception('monitoringdisabled', 'quizaccess_cdexamsave');
        }

        $now = time();
        $transaction = $DB->start_delegated_transaction();
        $session = self::ensure_session($attempt, $pagesessionid, $now);
        $updatesession = $action === 'init' || $session->pagesessionid === $pagesessionid;

        if ($action === 'init') {
            if ($session->pagesessionid !== $pagesessionid) {
                self::close_open_incidents($attempt->id, $now);
                $session->focuslost = 0;
                $session->lostsince = 0;
            }
            $session->pagesessionid = $pagesessionid;
        } else if ($action === 'lost') {
            $event = self::record_loss(
                $attempt,
                $pagesessionid,
                $eventuuid,
                $reason,
                $clienttime,
                $now
            );
            // A delayed beacon from an unloaded page must not overwrite the
            // focus state of a newer page session for the same attempt.
            if ($session->pagesessionid === $pagesessionid) {
                $session->focuslost = 1;
                $session->lostsince = (int) $event->timestart;
            }
        } else if ($action === 'returned') {
            self::record_return(
                $attempt,
                $pagesessionid,
                $eventuuid,
                $reason,
                $clienttime,
                $duration,
                $now
            );
            if ($session->pagesessionid === $pagesessionid) {
                $session->focuslost = 0;
                $session->lostsince = 0;
            }
        }

        if ($updatesession) {
            $session->active = 1;
            $session->lastheartbeat = $now;
            $session->timemodified = $now;
            $DB->update_record('quizaccess_cdexamsave_sess', $session);
        }
        $transaction->allow_commit();

        return [
            'accepted' => true,
            'servertime' => $now,
        ];
    }

    /**
     * Create or fetch the single current session for an attempt.
     *
     * @param \stdClass $attempt Attempt record.
     * @param string $pagesessionid Browser session UUID.
     * @param int $now Current timestamp.
     * @return \stdClass
     */
    private static function ensure_session(\stdClass $attempt, string $pagesessionid, int $now): \stdClass {
        global $DB;

        $session = $DB->get_record('quizaccess_cdexamsave_sess', ['attemptid' => $attempt->id]);
        if ($session) {
            return $session;
        }

        $record = (object) [
            'quizid' => $attempt->quiz,
            'attemptid' => $attempt->id,
            'userid' => $attempt->userid,
            'pagesessionid' => $pagesessionid,
            'active' => 1,
            'focuslost' => 0,
            'lostsince' => 0,
            'lastheartbeat' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $record->id = $DB->insert_record('quizaccess_cdexamsave_sess', $record);
        return $record;
    }

    /**
     * Insert the start of an incident unless it has already been received.
     *
     * @param \stdClass $attempt Attempt record.
     * @param string $pagesessionid Browser session UUID.
     * @param string $eventuuid Event UUID.
     * @param string $reason Detection reason.
     * @param int $clienttime Client timestamp.
     * @param int $now Server timestamp.
     * @return \stdClass
     */
    private static function record_loss(
        \stdClass $attempt,
        string $pagesessionid,
        string $eventuuid,
        string $reason,
        int $clienttime,
        int $now
    ): \stdClass {
        global $DB;

        $existing = $DB->get_record('quizaccess_cdexamsave_evt', ['eventuuid' => $eventuuid]);
        if ($existing) {
            self::assert_event_ownership($existing, $attempt);
            return $existing;
        }

        $maxincidents = (int) get_config('quizaccess_cdexamsave', 'maxincidents');
        $maxincidents = max(100, min(10000, $maxincidents ?: 2000));
        if ($DB->count_records('quizaccess_cdexamsave_evt', ['attemptid' => $attempt->id]) >= $maxincidents) {
            throw new \moodle_exception('incidentlimitreached', 'quizaccess_cdexamsave');
        }

        $event = (object) [
            'quizid' => $attempt->quiz,
            'attemptid' => $attempt->id,
            'userid' => $attempt->userid,
            'eventuuid' => $eventuuid,
            'pagesessionid' => $pagesessionid,
            'reason' => $reason,
            'timestart' => $now,
            'timeend' => 0,
            'duration' => 0,
            'clientstart' => $clienttime,
            'clientend' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $event->id = $DB->insert_record('quizaccess_cdexamsave_evt', $event);
        return $event;
    }

    /**
     * Close an incident. If the initial signal was lost in transit, create a
     * complete incident using the bounded client-reported duration.
     *
     * @param \stdClass $attempt Attempt record.
     * @param string $pagesessionid Browser session UUID.
     * @param string $eventuuid Event UUID.
     * @param string $reason Detection reason.
     * @param int $clienttime Client timestamp.
     * @param int $duration Client-reported duration in seconds.
     * @param int $now Server timestamp.
     * @return void
     */
    private static function record_return(
        \stdClass $attempt,
        string $pagesessionid,
        string $eventuuid,
        string $reason,
        int $clienttime,
        int $duration,
        int $now
    ): void {
        global $DB;

        $event = $DB->get_record('quizaccess_cdexamsave_evt', ['eventuuid' => $eventuuid]);
        if (!$event) {
            $event = self::record_loss(
                $attempt,
                $pagesessionid,
                $eventuuid,
                $reason,
                max(0, $clienttime - $duration),
                max((int) $attempt->timestart, $now - $duration)
            );
        }
        self::assert_event_ownership($event, $attempt);

        if (!empty($event->timeend)) {
            return;
        }

        $serverduration = max(0, $now - (int) $event->timestart);
        $event->timeend = $now;
        // Server chronology is authoritative. For a missing initial beacon,
        // record_loss() reconstructed a bounded start time above.
        $event->duration = $serverduration;
        $event->clientend = $clienttime;
        $event->timemodified = $now;
        $DB->update_record('quizaccess_cdexamsave_evt', $event);
    }

    /**
     * Close any incident left open by a previous browser session.
     *
     * @param int $attemptid Attempt ID.
     * @param int $now Current timestamp.
     * @return void
     */
    public static function close_open_incidents(int $attemptid, int $now): void {
        global $DB;

        $events = $DB->get_records('quizaccess_cdexamsave_evt', [
            'attemptid' => $attemptid,
            'timeend' => 0,
        ]);
        foreach ($events as $event) {
            $event->timeend = $now;
            $event->duration = max(0, $now - (int) $event->timestart);
            $event->timemodified = $now;
            $DB->update_record('quizaccess_cdexamsave_evt', $event);
        }
    }

    /**
     * Ensure a retried UUID cannot affect another user's incident.
     *
     * @param \stdClass $event Incident record.
     * @param \stdClass $attempt Attempt record.
     * @return void
     */
    private static function assert_event_ownership(\stdClass $event, \stdClass $attempt): void {
        if ((int) $event->attemptid !== (int) $attempt->id ||
                (int) $event->userid !== (int) $attempt->userid) {
            throw new \moodle_exception('invalidrequest', 'quizaccess_cdexamsave');
        }
    }

    /**
     * Validate a canonical UUID.
     *
     * @param string $value Value to validate.
     * @return bool
     */
    public static function is_valid_uuid(string $value): bool {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',
            $value
        );
    }

    /**
     * Return a safe, known reason identifier.
     *
     * @param mixed $reason Browser-supplied value.
     * @return string
     */
    public static function normalise_reason($reason): string {
        $reason = clean_param((string) $reason, PARAM_ALPHANUMEXT);
        return in_array($reason, self::REASONS, true) ? $reason : 'unknown';
    }
}
