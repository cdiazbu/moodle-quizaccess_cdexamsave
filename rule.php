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
 * Quiz access rule implementation for CD ExamFocus.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * CD ExamFocus access rule for Moodle quizzes.
 */
class quizaccess_cdexamsave extends \mod_quiz\local\access_rule_base {
    /**
     * Create the rule only when monitoring is enabled for this quiz.
     *
     * @param object $quizobj Quiz settings object.
     * @param int $timenow Current timestamp.
     * @param bool $canignoretimelimits Whether the current user can ignore limits.
     * @return self|null
     */
    public static function make($quizobj, $timenow, $canignoretimelimits) {
        $quiz = $quizobj->get_quiz();
        if (empty($quiz->cdexamsaveenabled)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * Add CD ExamFocus controls to the quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform Quiz form.
     * @param MoodleQuickForm $mform Moodle form.
     * @return void
     */
    public static function add_settings_form_fields($quizform, $mform) {
        $mform->addElement('header', 'cdexamsaveheader', get_string('formheader', 'quizaccess_cdexamsave'));
        $mform->setExpanded('cdexamsaveheader', false);

        $mform->addElement(
            'selectyesno',
            'cdexamsaveenabled',
            get_string('enabled', 'quizaccess_cdexamsave')
        );
        $mform->addHelpButton('cdexamsaveenabled', 'enabled', 'quizaccess_cdexamsave');
        $mform->setDefault('cdexamsaveenabled', 0);

        $mform->addElement(
            'selectyesno',
            'cdexamsavewarnstudent',
            get_string('warnstudent', 'quizaccess_cdexamsave')
        );
        $mform->addHelpButton('cdexamsavewarnstudent', 'warnstudent', 'quizaccess_cdexamsave');
        $mform->setDefault('cdexamsavewarnstudent', 1);
        $mform->disabledIf('cdexamsavewarnstudent', 'cdexamsaveenabled', 'eq', 0);

        $graceoptions = [
            0 => get_string('grace_none', 'quizaccess_cdexamsave'),
            500 => get_string('grace_halfsecond', 'quizaccess_cdexamsave'),
            1000 => get_string('grace_onesecond', 'quizaccess_cdexamsave'),
            2000 => get_string('grace_twoseconds', 'quizaccess_cdexamsave'),
            3000 => get_string('grace_threeseconds', 'quizaccess_cdexamsave'),
        ];
        $mform->addElement(
            'select',
            'cdexamsavegraceperiodms',
            get_string('graceperiod', 'quizaccess_cdexamsave'),
            $graceoptions
        );
        $mform->addHelpButton('cdexamsavegraceperiodms', 'graceperiod', 'quizaccess_cdexamsave');
        $mform->setDefault('cdexamsavegraceperiodms', 1000);
        $mform->disabledIf('cdexamsavegraceperiodms', 'cdexamsaveenabled', 'eq', 0);
    }

    /**
     * Validate quiz settings.
     *
     * @param array $errors Existing errors.
     * @param array $data Submitted form data.
     * @param array $files Submitted files.
     * @param mod_quiz_mod_form $quizform Quiz form.
     * @return array
     */
    public static function validate_settings_form_fields($errors, $data, $files, $quizform) {
        $allowed = [0, 500, 1000, 2000, 3000];
        if (
            !empty($data['cdexamsaveenabled']) &&
            !in_array((int) ($data['cdexamsavegraceperiodms'] ?? -1), $allowed, true)
        ) {
            $errors['cdexamsavegraceperiodms'] = get_string('invalidgraceperiod', 'quizaccess_cdexamsave');
        }
        return $errors;
    }

    /**
     * Save per-quiz settings.
     *
     * @param stdClass $quiz Quiz record plus form data.
     * @return void
     */
    public static function save_settings($quiz) {
        global $DB;

        if (empty($quiz->cdexamsaveenabled)) {
            $DB->delete_records('quizaccess_cdexamsave', ['quizid' => $quiz->id]);
            return;
        }

        $now = time();
        $record = $DB->get_record('quizaccess_cdexamsave', ['quizid' => $quiz->id]);
        if (!$record) {
            $record = (object) [
                'quizid' => $quiz->id,
                'timecreated' => $now,
            ];
        }
        $record->enabled = 1;
        $record->warnstudent = empty($quiz->cdexamsavewarnstudent) ? 0 : 1;
        $record->graceperiodms = (int) ($quiz->cdexamsavegraceperiodms ?? 1000);
        $record->timemodified = $now;

        if (empty($record->id)) {
            $DB->insert_record('quizaccess_cdexamsave', $record);
        } else {
            $DB->update_record('quizaccess_cdexamsave', $record);
        }
    }

    /**
     * Delete per-quiz settings. Attempt data is removed through its foreign
     * relationship and by the standard quiz deletion workflow.
     *
     * @param stdClass $quiz Quiz record.
     * @return void
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_cdexamsave', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_cdexamsave_evt', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_cdexamsave_sess', ['quizid' => $quiz->id]);
    }

    /**
     * Load settings in the quiz access-manager query.
     *
     * @param int $quizid Quiz ID.
     * @return array SQL fields, joins and parameters.
     */
    public static function get_settings_sql($quizid) {
        return [
            'cds.enabled AS cdexamsaveenabled, ' .
                'cds.warnstudent AS cdexamsavewarnstudent, ' .
                'cds.graceperiodms AS cdexamsavegraceperiodms',
            'LEFT JOIN {quizaccess_cdexamsave} cds ON cds.quizid = quiz.id',
            [],
        ];
    }

    /**
     * Initialise monitoring on an in-progress, non-preview attempt page.
     *
     * @param moodle_page $page Page object.
     * @return void
     */
    public function setup_attempt_page($page) {
        global $CFG, $DB, $USER;

        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if (!$attemptid) {
            return;
        }

        $attempt = $DB->get_record('quiz_attempts', [
            'id' => $attemptid,
            'quiz' => $this->quiz->id,
            'userid' => $USER->id,
            'preview' => 0,
            'state' => 'inprogress',
        ]);
        if (!$attempt) {
            return;
        }

        $cm = get_coursemodule_from_instance('quiz', $this->quiz->id, $this->quiz->course, false, MUST_EXIST);
        $heartbeat = max(5, min(60, (int) get_config('quizaccess_cdexamsave', 'heartbeatinterval')));
        $config = [
            'attemptId' => (int) $attemptid,
            'cmId' => (int) $cm->id,
            'collectorUrl' => $CFG->wwwroot . '/mod/quiz/accessrule/cdexamsave/collector.php',
            'gracePeriodMs' => (int) $this->quiz->cdexamsavegraceperiodms,
            'heartbeatMs' => $heartbeat * 1000,
            'warnStudent' => !empty($this->quiz->cdexamsavewarnstudent),
            'strings' => [
                'badge' => get_string('monitoringbadge', 'quizaccess_cdexamsave'),
                'warningTitle' => get_string('studentwarningtitle', 'quizaccess_cdexamsave'),
                'warningText' => get_string('studentwarningtext', 'quizaccess_cdexamsave'),
                'continue' => get_string('continueattempt', 'quizaccess_cdexamsave'),
                'duration' => get_string('studentwarningduration', 'quizaccess_cdexamsave'),
            ],
        ];
        $page->requires->js_call_amd('quizaccess_cdexamsave/monitor', 'init', [$config]);
    }

    /**
     * Explain the active rule and expose the live report to authorised staff.
     *
     * @return array
     */
    public function description() {
        $messages = [get_string('monitoringnotice', 'quizaccess_cdexamsave')];
        $cm = get_coursemodule_from_instance('quiz', $this->quiz->id, $this->quiz->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        if (has_capability('quizaccess/cdexamsave:viewreport', $context)) {
            $url = new moodle_url('/mod/quiz/accessrule/cdexamsave/report.php', ['cmid' => $cm->id]);
            $messages[] = html_writer::link(
                $url,
                get_string('openlivereport', 'quizaccess_cdexamsave'),
                ['class' => 'btn btn-secondary btn-sm']
            );
        }
        return $messages;
    }
}
