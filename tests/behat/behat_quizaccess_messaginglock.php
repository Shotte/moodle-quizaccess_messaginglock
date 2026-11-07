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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;
use quizaccess_messaginglock\lock_manager;

/**
 * Steps for the messaging lock: server-side checks of the lock and of the exam time.
 *
 * The lock is a capability, so the decisive check is the capability itself, not
 * how Moodle's messaging drawer renders it.
 *
 * @package    quizaccess_messaginglock
 * @category   test
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_quizaccess_messaginglock extends behat_base {
    /**
     * Checks lock assignment and effective capability of a user.
     *
     * @Then /^"(?P<username>[^"]*)" (?P<expectation>should|should not) be able to send Moodle messages$/
     * @param string $username
     * @param string $expectation
     */
    public function user_can_send_moodle_messages(string $username, string $expectation): void {
        global $DB;
        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        // Locks are changed by web requests and the cron step; drop this process' access cache first.
        accesslib_clear_all_caches(true);
        $cansend = has_capability('moodle/site:sendmessage', context_system::instance(), $user);
        $locked = $DB->record_exists('role_assignments', ['userid' => $user->id, 'component' => lock_manager::COMPONENT]);
        $wanted = $expectation === 'should';
        if ($cansend !== $wanted || $locked === $wanted) {
            $message = "$username: can send = " . var_export($cansend, true)
                . ', lock assignments = ' . var_export($locked, true) . ", expected $expectation";
            throw new ExpectationException($message, $this->getSession());
        }
    }

    /**
     * Checks the stored setting of a quiz.
     *
     * @Then /^the quiz "(?P<name>[^"]*)" should have the messaging lock (?P<state>enabled|disabled)$/
     * @param string $name
     * @param string $state
     */
    public function quiz_lock_state(string $name, string $state): void {
        global $DB;
        $quiz = $DB->get_record('quiz', ['name' => $name], '*', MUST_EXIST);
        if (lock_manager::is_enabled((int) $quiz->id) !== ($state === 'enabled')) {
            throw new ExpectationException("Quiz '$name' does not have the messaging lock $state", $this->getSession());
        }
    }

    /**
     * Moves every attempt of a quiz one day into the past, so its exam time has ended.
     *
     * @Given /^the exam time of quiz "(?P<name>[^"]*)" has passed$/
     * @param string $name
     */
    public function exam_time_has_passed(string $name): void {
        global $DB;
        $quiz = $DB->get_record('quiz', ['name' => $name], '*', MUST_EXIST);
        $DB->execute('UPDATE {quiz_attempts}
                         SET timestart = timestart - :shift1,
                             timecheckstate = CASE WHEN timecheckstate IS NULL THEN NULL ELSE timecheckstate - :shift2 END
                       WHERE quiz = :quizid', ['shift1' => 2 * DAYSECS, 'shift2' => 2 * DAYSECS, 'quizid' => $quiz->id]);
    }
}
