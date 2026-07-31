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

namespace quizaccess_cdexamsave\task;

/**
 * Deletes monitoring data after the configured retention period.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup extends \core\task\scheduled_task {
    /** @var int Maximum rows removed per transaction-sized DML call. */
    private const BATCH_SIZE = 5000;

    /**
     * Return the translated task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskcleanup', 'quizaccess_cdexamsave');
    }

    /**
     * Execute retention cleanup.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $retentiondays = (int) get_config('quizaccess_cdexamsave', 'retentiondays');
        $retentiondays = max(1, $retentiondays ?: 180);
        $cutoff = time() - ($retentiondays * DAYSECS);

        self::delete_expired_in_batches('quizaccess_cdexamsave_evt', 'timecreated', $cutoff);
        self::delete_expired_in_batches('quizaccess_cdexamsave_sess', 'timemodified', $cutoff);
    }

    /**
     * Delete old rows in bounded batches to avoid a long table lock on large
     * examination sites.
     *
     * @param string $table Moodle table name.
     * @param string $timefield Indexed timestamp field.
     * @param int $cutoff Retention cutoff.
     * @return void
     */
    private static function delete_expired_in_batches(string $table, string $timefield, int $cutoff): void {
        global $DB;

        do {
            $records = $DB->get_records_select(
                $table,
                "{$timefield} < :cutoff",
                ['cutoff' => $cutoff],
                'id ASC',
                'id',
                0,
                self::BATCH_SIZE
            );
            $ids = array_map('intval', array_keys($records));
            if ($ids) {
                $DB->delete_records_list($table, 'id', $ids);
            }
        } while (count($ids) === self::BATCH_SIZE);
    }
}
