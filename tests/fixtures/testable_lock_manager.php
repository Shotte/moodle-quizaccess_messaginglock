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

/**
 * Test double for lock_manager: inject a failure or an action into the release of a quiz.
 *
 * @package    quizaccess_messaginglock
 * @category   test
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_lock_manager extends lock_manager {
    /** @var int Quiz whose release throws. */
    public static $failfor = 0;

    /** @var callable|null Runs right before the release, e.g. an attempt started concurrently. */
    public static $beforerelease = null;

    /**
     * Throws for the chosen quiz, runs the injected action, then releases normally.
     *
     * @param int $quizid
     * @param int $now
     */
    protected static function release_quiz(int $quizid, int $now): void {
        if ($quizid === self::$failfor) {
            throw new \coding_exception('injected release failure');
        }
        if (self::$beforerelease) {
            (self::$beforerelease)();
        }
        parent::release_quiz($quizid, $now);
    }
}
