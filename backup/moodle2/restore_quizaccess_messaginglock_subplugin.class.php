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
 * Restore of the messaging lock setting.
 *
 * @package    quizaccess_messaginglock
 * @category   backup
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/restore_mod_quiz_access_subplugin.class.php');

/**
 * Restores only the setting, onto the newly created quiz. Never creates locks: they arise only
 * when an attempt starts (or is opened) on this site. Without the element the quiz stays disabled.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_quizaccess_messaginglock_subplugin extends restore_mod_quiz_access_subplugin {
    /**
     * Paths of the quiz setting.
     *
     * @return restore_path_element[]
     */
    protected function define_quiz_subplugin_structure() {
        return [new restore_path_element('quizaccess_messaginglock', $this->get_pathfor('/quizaccess_messaginglock'))];
    }

    /**
     * Enables the lock for the restored quiz.
     *
     * @param array|stdClass $data
     */
    public function process_quizaccess_messaginglock($data) {
        global $DB;
        $data = (object) $data;
        $quizid = $this->get_new_parentid('quiz');
        if (empty($data->enabled) || !$quizid || $DB->record_exists('quizaccess_messaginglock', ['quizid' => $quizid])) {
            return;
        }
        $DB->insert_record('quizaccess_messaginglock', (object) ['quizid' => $quizid]);
    }
}
