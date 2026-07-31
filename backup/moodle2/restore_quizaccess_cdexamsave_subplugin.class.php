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
 * Restore support for per-quiz CD ExamFocus settings.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_quizaccess_cdexamsave_subplugin extends restore_mod_quiz_access_subplugin {
    /**
     * Define the settings restore path.
     *
     * @return restore_path_element[]
     */
    protected function define_quiz_subplugin_structure() {
        return [
            new restore_path_element(
                'cdexamsave_setting',
                $this->get_pathfor('/cdexamsave_setting')
            ),
        ];
    }

    /**
     * Restore one settings record for the new quiz.
     *
     * @param array $data Restored XML values.
     * @return void
     */
    public function process_cdexamsave_setting($data): void {
        global $DB;

        $record = (object) $data;
        unset($record->id);
        $record->quizid = $this->get_new_parentid('quiz');
        $record->timecreated = time();
        $record->timemodified = time();

        $existing = $DB->get_record('quizaccess_cdexamsave', ['quizid' => $record->quizid]);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('quizaccess_cdexamsave', $record);
        } else {
            $DB->insert_record('quizaccess_cdexamsave', $record);
        }
    }
}
