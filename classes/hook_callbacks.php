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

namespace quizaccess_messaginglock;

use mod_quiz\hook\attempt_state_changed;

/**
 * Hook callbacks.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Locks when an attempt starts or is reopened.
     *
     * Runs inside the transaction that starts the attempt, so an exception here rolls the
     * start back: the attempt cannot begin without its lock (fail closed). Releasing is left
     * to the scheduled task on purpose: submit/finish/abandon never shorten the lock period,
     * and a release error must never block a submission.
     *
     * @param attempt_state_changed $hook
     */
    public static function attempt_state_changed(attempt_state_changed $hook): void {
        $attempt = $hook->get_updated_attempt();
        if (!$attempt || !empty($attempt->preview) || $attempt->state !== 'inprogress') {
            return;
        }
        if (lock_manager::is_enabled((int) $attempt->quiz)) {
            lock_manager::lock((int) $attempt->quiz, (int) $attempt->userid);
        }
    }
}
