<?php
// This file is part of Moodle - http://moodle.org/

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
