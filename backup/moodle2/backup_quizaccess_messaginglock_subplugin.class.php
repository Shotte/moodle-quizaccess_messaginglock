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
 * Backup of the messaging lock setting.
 *
 * @package    quizaccess_messaginglock
 * @category   backup
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/backup_mod_quiz_access_subplugin.class.php');

/**
 * Writes only the quiz setting. Lock role assignments (system context, per user) are never backed up.
 *
 * Used by every backup of a quiz: activity and course backup, import, course copy and duplication.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_quizaccess_messaginglock_subplugin extends backup_mod_quiz_access_subplugin {
    /**
     * Element <quizaccess_messaginglock><enabled>1</enabled></...>, present only when the lock is enabled.
     *
     * @return backup_subplugin_element
     */
    protected function define_quiz_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $setting = new backup_nested_element('quizaccess_messaginglock', null, ['enabled']);
        $subplugin->add_child($wrapper);
        $wrapper->add_child($setting);
        $setting->set_source_sql(
            'SELECT id, 1 AS enabled FROM {quizaccess_messaginglock} WHERE quizid = ?',
            [backup::VAR_ACTIVITYID]
        );
        return $subplugin;
    }
}
