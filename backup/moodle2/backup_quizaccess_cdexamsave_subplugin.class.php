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
