<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Backup support for per-quiz CD ExamFocus settings.
 *
 * Monitoring incidents are intentionally not copied because they belong to
 * specific users and attempts, not to the reusable quiz definition.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_quizaccess_cdexamsave_subplugin extends backup_mod_quiz_access_subplugin {
    /**
     * Define settings included in quiz backup.
     *
     * @return backup_subplugin_element
     */
    protected function define_quiz_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $setting = new backup_nested_element(
            'cdexamsave_setting',
            ['id'],
            ['enabled', 'warnstudent', 'graceperiodms', 'timecreated', 'timemodified']
        );
        $subplugin->add_child($wrapper);
        $wrapper->add_child($setting);
        $setting->set_source_table('quizaccess_cdexamsave', [
            'quizid' => backup::VAR_ACTIVITYID,
        ]);
        return $subplugin;
    }
}
