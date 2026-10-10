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
 * Quiz access rule implementation for CD Exam Control.
 *
 * @package    quizaccess_cdexamcontrol
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * CD Exam Control access rule for Moodle quizzes.
 */
class quizaccess_cdexamcontrol extends \mod_quiz\local\access_rule_base {
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
        if (empty($quiz->cdexamcontrolenabled)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * Add CD Exam Control controls to the quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform Quiz form.
     * @param MoodleQuickForm $mform Moodle form.
     * @return void
     */
    public static function add_settings_form_fields($quizform, $mform) {
        $mform->addElement('header', 'cdexamcontrolheader', get_string('formheader', 'quizaccess_cdexamcontrol'));
        $mform->setExpanded('cdexamcontrolheader', false);

        $mform->addElement(
            'selectyesno',
            'cdexamcontrolenabled',
            get_string('enabled', 'quizaccess_cdexamcontrol')
        );
        $mform->addHelpButton('cdexamcontrolenabled', 'enabled', 'quizaccess_cdexamcontrol');
        $mform->setDefault('cdexamcontrolenabled', 0);

        $mform->addElement(
            'selectyesno',
            'cdexamcontrolwarnstudent',
            get_string('warnstudent', 'quizaccess_cdexamcontrol')
        );
        $mform->addHelpButton('cdexamcontrolwarnstudent', 'warnstudent', 'quizaccess_cdexamcontrol');
        $mform->setDefault('cdexamcontrolwarnstudent', 1);
        $mform->disabledIf('cdexamcontrolwarnstudent', 'cdexamcontrolenabled', 'eq', 0);

        foreach (['requirefullscreen' => 1, 'blockshortcuts' => 0] as $setting => $default) {
            $name = 'cdexamcontrol' . $setting;
            $mform->addElement('selectyesno', $name, get_string($setting, 'quizaccess_cdexamcontrol'));
            $mform->addHelpButton($name, $setting, 'quizaccess_cdexamcontrol');
            $mform->setDefault($name, $default);
            $mform->disabledIf($name, 'cdexamcontrolenabled', 'eq', 0);
        }

        $graceoptions = [
            0 => get_string('grace_none', 'quizaccess_cdexamcontrol'),
            500 => get_string('grace_halfsecond', 'quizaccess_cdexamcontrol'),
            1000 => get_string('grace_onesecond', 'quizaccess_cdexamcontrol'),
            2000 => get_string('grace_twoseconds', 'quizaccess_cdexamcontrol'),
            3000 => get_string('grace_threeseconds', 'quizaccess_cdexamcontrol'),
        ];
        $mform->addElement(
            'select',
            'cdexamcontrolgraceperiodms',
            get_string('graceperiod', 'quizaccess_cdexamcontrol'),
            $graceoptions
        );
        $mform->addHelpButton('cdexamcontrolgraceperiodms', 'graceperiod', 'quizaccess_cdexamcontrol');
        $mform->setDefault('cdexamcontrolgraceperiodms', 1000);
        $mform->disabledIf('cdexamcontrolgraceperiodms', 'cdexamcontrolenabled', 'eq', 0);
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
            !empty($data['cdexamcontrolenabled']) &&
            !in_array((int) ($data['cdexamcontrolgraceperiodms'] ?? -1), $allowed, true)
        ) {
            $errors['cdexamcontrolgraceperiodms'] = get_string('invalidgraceperiod', 'quizaccess_cdexamcontrol');
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

        if (empty($quiz->cdexamcontrolenabled)) {
            $DB->delete_records('quizaccess_cdexamcontrol', ['quizid' => $quiz->id]);
            return;
        }

        $now = time();
        $record = $DB->get_record('quizaccess_cdexamcontrol', ['quizid' => $quiz->id]);
        if (!$record) {
            $record = (object) [
                'quizid' => $quiz->id,
                'timecreated' => $now,
            ];
        }
        $record->enabled = 1;
        $record->warnstudent = empty($quiz->cdexamcontrolwarnstudent) ? 0 : 1;
        $record->requirefullscreen = (int) ($quiz->cdexamcontrolrequirefullscreen ?? 1) ? 1 : 0;
        $record->blockshortcuts = empty($quiz->cdexamcontrolblockshortcuts) ? 0 : 1;
        $record->graceperiodms = (int) ($quiz->cdexamcontrolgraceperiodms ?? 1000);
        $record->timemodified = $now;

        if (empty($record->id)) {
            $DB->insert_record('quizaccess_cdexamcontrol', $record);
        } else {
            $DB->update_record('quizaccess_cdexamcontrol', $record);
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
        $DB->delete_records('quizaccess_cdexamcontrol', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_cdexamcontrol_evt', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_cdexamctrl_sess', ['quizid' => $quiz->id]);
    }

    /**
     * Load settings in the quiz access-manager query.
     *
     * @param int $quizid Quiz ID.
     * @return array SQL fields, joins and parameters.
     */
    public static function get_settings_sql($quizid) {
        return [
            'cds.enabled AS cdexamcontrolenabled, ' .
                'cds.warnstudent AS cdexamcontrolwarnstudent, ' .
                'cds.graceperiodms AS cdexamcontrolgraceperiodms, ' .
                'cds.requirefullscreen AS cdexamcontrolrequirefullscreen, ' .
                'cds.blockshortcuts AS cdexamcontrolblockshortcuts',
            'LEFT JOIN {quizaccess_cdexamcontrol} cds ON cds.quizid = quiz.id',
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
        global $DB, $USER;

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
        $context = context_module::instance($cm->id);
        if (has_capability('quizaccess/cdexamcontrol:exempt', $context)) {
            return;
        }
        $heartbeat = (int) get_config('quizaccess_cdexamcontrol', 'heartbeatinterval');
        $heartbeat = max(5, min(60, $heartbeat ?: 10));
        $config = [
            'attemptId' => (int) $attemptid,
            'userId' => (int) $USER->id,
            'requireFullscreen' => !empty($this->quiz->cdexamcontrolrequirefullscreen),
            'blockShortcuts' => !empty($this->quiz->cdexamcontrolblockshortcuts),
            'cmId' => (int) $cm->id,
            'gracePeriodMs' => (int) $this->quiz->cdexamcontrolgraceperiodms,
            'heartbeatMs' => $heartbeat * 1000,
            'warnStudent' => !empty($this->quiz->cdexamcontrolwarnstudent),
            'strings' => [
                'badge' => get_string('monitoringbadge', 'quizaccess_cdexamcontrol'),
                'connecting' => get_string('monitorconnecting', 'quizaccess_cdexamcontrol'),
                'pending' => get_string('monitorpending', 'quizaccess_cdexamcontrol'),
                'stopped' => get_string('monitorstopped', 'quizaccess_cdexamcontrol'),
                'queueFull' => get_string('monitorqueuefull', 'quizaccess_cdexamcontrol'),
                'fullscreenTitle' => get_string('fullscreentitle', 'quizaccess_cdexamcontrol'),
                'fullscreenText' => get_string('fullscreentext', 'quizaccess_cdexamcontrol'),
                'fullscreenButton' => get_string('fullscreenbutton', 'quizaccess_cdexamcontrol'),
                'fullscreenError' => get_string('fullscreenerror', 'quizaccess_cdexamcontrol'),
                'fullscreenUnsupported' => get_string('fullscreenunsupported', 'quizaccess_cdexamcontrol'),
                'shortcut' => get_string('shortcutnotice', 'quizaccess_cdexamcontrol'),
                'warningTitle' => get_string('studentwarningtitle', 'quizaccess_cdexamcontrol'),
                'warningText' => get_string('studentwarningtext', 'quizaccess_cdexamcontrol'),
                'continue' => get_string('continueattempt', 'quizaccess_cdexamcontrol'),
                'duration' => get_string('studentwarningduration', 'quizaccess_cdexamcontrol'),
            ],
        ];
        $page->requires->js_call_amd('quizaccess_cdexamcontrol/monitor', 'init', [$config]);
    }

    /**
     * Explain the active rule and expose the live report to authorised staff.
     *
     * @return array
     */
    public function description() {
        $messages = [get_string('monitoringnotice', 'quizaccess_cdexamcontrol')];
        $cm = get_coursemodule_from_instance('quiz', $this->quiz->id, $this->quiz->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        if (has_capability('quizaccess/cdexamcontrol:viewreport', $context)) {
            $url = new moodle_url('/mod/quiz/accessrule/cdexamcontrol/report.php', ['cmid' => $cm->id]);
            $messages[] = html_writer::link(
                $url,
                get_string('openlivereport', 'quizaccess_cdexamcontrol'),
                ['class' => 'btn btn-secondary btn-sm']
            );
        }
        return $messages;
    }
}
