<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_cdexamsave\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation for CDexamSave.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno <carlosdiazbueno@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'quizaccess_cdexamsave_evt',
            [
                'quizid' => 'privacy:metadata:quizaccess_cdexamsave_evt:quizid',
                'attemptid' => 'privacy:metadata:quizaccess_cdexamsave_evt:attemptid',
                'userid' => 'privacy:metadata:quizaccess_cdexamsave_evt:userid',
                'eventuuid' => 'privacy:metadata:quizaccess_cdexamsave_evt:eventuuid',
                'pagesessionid' => 'privacy:metadata:quizaccess_cdexamsave_evt:pagesessionid',
                'reason' => 'privacy:metadata:quizaccess_cdexamsave_evt:reason',
                'timestart' => 'privacy:metadata:quizaccess_cdexamsave_evt:times',
                'timeend' => 'privacy:metadata:quizaccess_cdexamsave_evt:times',
                'duration' => 'privacy:metadata:quizaccess_cdexamsave_evt:times',
                'clientstart' => 'privacy:metadata:quizaccess_cdexamsave_evt:times',
                'clientend' => 'privacy:metadata:quizaccess_cdexamsave_evt:times',
            ],
            'privacy:metadata:quizaccess_cdexamsave_evt'
        );
        $collection->add_database_table(
            'quizaccess_cdexamsave_sess',
            [
                'quizid' => 'privacy:metadata:quizaccess_cdexamsave_sess:quizid',
                'attemptid' => 'privacy:metadata:quizaccess_cdexamsave_sess:attemptid',
                'userid' => 'privacy:metadata:quizaccess_cdexamsave_sess:userid',
                'pagesessionid' => 'privacy:metadata:quizaccess_cdexamsave_sess:pagesessionid',
                'active' => 'privacy:metadata:quizaccess_cdexamsave_sess:state',
                'focuslost' => 'privacy:metadata:quizaccess_cdexamsave_sess:state',
                'lostsince' => 'privacy:metadata:quizaccess_cdexamsave_sess:state',
                'lastheartbeat' => 'privacy:metadata:quizaccess_cdexamsave_sess:state',
            ],
            'privacy:metadata:quizaccess_cdexamsave_sess'
        );
        return $collection;
    }

    /**
     * Find activity contexts containing data for a user.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modulename' => 'quiz',
            'userid' => $userid,
        ];
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {quizaccess_cdexamsave_evt} e ON e.quizid = cm.instance
                 WHERE ctx.contextlevel = :contextlevel AND e.userid = :userid";
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {quizaccess_cdexamsave_sess} s ON s.quizid = cm.instance
                 WHERE ctx.contextlevel = :contextlevel AND s.userid = :userid";
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export a user's monitoring records.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $quizid = self::quizid_from_context($context);
            if (!$quizid) {
                continue;
            }
            $events = array_values($DB->get_records('quizaccess_cdexamsave_evt', [
                'quizid' => $quizid,
                'userid' => $userid,
            ], 'timestart ASC'));
            $sessions = array_values($DB->get_records('quizaccess_cdexamsave_sess', [
                'quizid' => $quizid,
                'userid' => $userid,
            ]));
            if (!$events && !$sessions) {
                continue;
            }
            writer::with_context($context)->export_data(
                [get_string('privacy:path', 'quizaccess_cdexamsave')],
                (object) [
                    'incidents' => $events,
                    'sessions' => $sessions,
                ]
            );
        }
    }

    /**
     * Delete all user data in one activity context.
     *
     * @param \context $context Context to erase.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        $quizid = self::quizid_from_context($context);
        if (!$quizid) {
            return;
        }
        $DB->delete_records('quizaccess_cdexamsave_evt', ['quizid' => $quizid]);
        $DB->delete_records('quizaccess_cdexamsave_sess', ['quizid' => $quizid]);
    }

    /**
     * Delete one user's data from approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $quizid = self::quizid_from_context($context);
            if (!$quizid) {
                continue;
            }
            $conditions = ['quizid' => $quizid, 'userid' => $userid];
            $DB->delete_records('quizaccess_cdexamsave_evt', $conditions);
            $DB->delete_records('quizaccess_cdexamsave_sess', $conditions);
        }
    }

    /**
     * Add users with data in an activity context to a user list.
     *
     * @param userlist $userlist Target user list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $quizid = self::quizid_from_context($userlist->get_context());
        if (!$quizid) {
            return;
        }
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {quizaccess_cdexamsave_evt} WHERE quizid = :quizid',
            ['quizid' => $quizid]
        );
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {quizaccess_cdexamsave_sess} WHERE quizid = :quizid',
            ['quizid' => $quizid]
        );
    }

    /**
     * Delete several users' data from one approved context.
     *
     * @param approved_userlist $userlist Approved users and context.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $quizid = self::quizid_from_context($userlist->get_context());
        $userids = array_map('intval', $userlist->get_userids());
        if (!$quizid || !$userids) {
            return;
        }
        [$usersql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'privacyuser');
        $params['quizid'] = $quizid;
        $DB->delete_records_select(
            'quizaccess_cdexamsave_evt',
            "quizid = :quizid AND userid {$usersql}",
            $params
        );
        $DB->delete_records_select(
            'quizaccess_cdexamsave_sess',
            "quizid = :quizid AND userid {$usersql}",
            $params
        );
    }

    /**
     * Resolve a quiz instance from a module context.
     *
     * @param \context $context Context.
     * @return int|null
     */
    private static function quizid_from_context(\context $context): ?int {
        if (!$context instanceof \context_module) {
            return null;
        }
        $cm = get_coursemodule_from_id('quiz', $context->instanceid, 0, false, IGNORE_MISSING);
        return $cm ? (int) $cm->instance : null;
    }
}
