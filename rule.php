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

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;
use quizaccess_messaginglock\lock_manager;

/**
 * Quiz access rule: lock private Moodle messaging while the exam is running.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_messaginglock extends access_rule_base {
    /**
     * Creates the rule if the lock is enabled for this quiz.
     *
     * @param quiz_settings $quizobj
     * @param int $timenow
     * @param bool $canignoretimelimits
     * @return self|null
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        if (empty($quizobj->get_quiz()->messaginglockenabled)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * Fail closed: a user inside the lock period without a lock is re-locked, or access is refused.
     *
     * @return string|false
     */
    public function prevent_access() {
        global $USER;

        $quizid = (int) $this->quiz->id;
        $userid = (int) $USER->id;
        $inperiod = lock_manager::active_userids($quizid, $this->timenow, $userid) !== [];
        if (!$inperiod || lock_manager::is_locked($quizid, $userid)) {
            return false;
        }
        try {
            lock_manager::lock($quizid, $userid);
            return false;
        } catch (\Throwable $e) {
            debugging('quizaccess_messaginglock: lock failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return get_string('lockfailed', 'quizaccess_messaginglock');
        }
    }

    /**
     * Shown to students on the quiz start page.
     *
     * @return string
     */
    public function description() {
        return get_string('studentinfo', 'quizaccess_messaginglock');
    }

    /**
     * Adds the single teacher-facing switch.
     *
     * @param mod_quiz_mod_form $quizform
     * @param MoodleQuickForm $mform
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        // Same element and pattern as core quizaccess_offlineattempts: label column left, No/Yes select right.
        $mform->addElement(
            'selectyesno',
            'messaginglockenabled',
            get_string('messaginglockenabled', 'quizaccess_messaginglock')
        );
        $mform->addHelpButton('messaginglockenabled', 'messaginglockenabled', 'quizaccess_messaginglock');
        // Default only for new quizzes: when editing, mod_quiz_mod_form::data_preprocessing() loads the
        // stored value (no row = 0) through access_manager::load_settings() and overrides it.
        $mform->setDefault('messaginglockenabled', 1);
    }

    /**
     * Saves the setting. Disabling it releases the quiz's locks immediately.
     *
     * Code that saves a quiz without the form field (scripts, other plugins) leaves the stored
     * setting untouched: a running lock must never be switched off by omission.
     *
     * @param stdClass $quiz
     */
    public static function save_settings($quiz) {
        global $DB;

        if (!property_exists($quiz, 'messaginglockenabled')) {
            return;
        }
        if (empty($quiz->messaginglockenabled)) {
            $DB->delete_records('quizaccess_messaginglock', ['quizid' => $quiz->id]);
            self::release_now((int) $quiz->id);
        } else if (!lock_manager::is_enabled((int) $quiz->id)) {
            $DB->insert_record('quizaccess_messaginglock', (object) ['quizid' => $quiz->id]);
        }
    }

    /**
     * Deletes the setting and releases the quiz's locks.
     *
     * @param stdClass $quiz
     */
    public static function delete_settings($quiz) {
        global $DB;

        $DB->delete_records('quizaccess_messaginglock', ['quizid' => $quiz->id]);
        self::release_now((int) $quiz->id);
    }

    /**
     * Releases the quiz's locks at once; on error the scheduled task does it within a minute.
     * Saving or deleting the quiz must not fail because of the release.
     *
     * @param int $quizid
     */
    private static function release_now(int $quizid): void {
        try {
            lock_manager::release_ended($quizid);
        } catch (\Throwable $e) {
            debugging('quizaccess_messaginglock: release deferred to cron: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Loads the setting with the other quiz settings.
     *
     * @param int $quizid
     * @return array
     */
    public static function get_settings_sql($quizid) {
        return [
            'CASE WHEN messaginglock.id IS NULL THEN 0 ELSE 1 END AS messaginglockenabled',
            'LEFT JOIN {quizaccess_messaginglock} messaginglock ON messaginglock.quizid = quiz.id',
            [],
        ];
    }
}
