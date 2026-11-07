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

namespace quizaccess_messaginglock\task;

use quizaccess_messaginglock\lock_manager;

/**
 * Releases messaging locks of exams that are no longer running.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class release_locks extends \core\task\scheduled_task {
    /** @var string Lock manager class; replaced only by tests to inject failures. */
    public $manager = lock_manager::class;

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskreleaselocks', 'quizaccess_messaginglock');
    }

    /**
     * Runs the task.
     */
    public function execute() {
        try {
            $released = $this->manager::release_ended();
            mtrace(get_string('taskreleaselocksdone', 'quizaccess_messaginglock', $released));
        } catch (\moodle_exception $e) {
            // Not rethrown: a failed task is backed off up to a day by core, which would delay the
            // release of every other quiz. The failing quiz keeps its locks and is retried next minute.
            mtrace(get_string('taskreleaselocksfailed', 'quizaccess_messaginglock', $e->getMessage()));
            debugging('quizaccess_messaginglock: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
