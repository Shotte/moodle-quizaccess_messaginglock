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

use core_message\api;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/message/externallib.php');
require_once(__DIR__ . '/fixtures/testable_lock_manager.php');

/**
 * Lock, enforcement and release tests. Numbers refer to docs/testplan.md.
 *
 * Every lock assertion checks both the role assignment and the resulting capability.
 *
 * @package    quizaccess_messaginglock
 * @category   test
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(lock_manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(hook_callbacks::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(task\release_locks::class)]
final class lock_manager_test extends \advanced_testcase {
    /** @var \stdClass */
    private $course;

    /** @var \mod_quiz_generator */
    private $quizgen;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->quizgen = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
    }

    /**
     * Creates a quiz with one question and the lock enabled unless stated otherwise.
     *
     * @param array $settings
     * @return \stdClass quiz record
     */
    private function make_quiz(array $settings = []): \stdClass {
        $settings += ['course' => $this->course->id, 'messaginglockenabled' => 1];
        return $this->quizgen->create_test_quiz([['TF1', 1, 'truefalse']], $settings)->get_quiz();
    }

    /**
     * Deletes a quiz the way the course page does.
     *
     * @param \stdClass $quiz
     */
    private function delete_quiz(\stdClass $quiz): void {
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, 0, false, MUST_EXIST);
        if (method_exists(\core_courseformat\local\cmactions::class, 'delete')) {
            // Moodle 5.2+: course_delete_module() is deprecated in 5.3 in favour of this.
            \core_courseformat\formatactions::cm($cm->course)->delete($cm->id);
        } else {
            course_delete_module($cm->id);
        }
    }

    /**
     * Creates an enrolled student.
     *
     * @return \stdClass
     */
    private function make_student(): \stdClass {
        return $this->getDataGenerator()->create_and_enrol($this->course, 'student');
    }

    /**
     * Starts an attempt as the given user, optionally moved back to a given start time.
     *
     * Moving timestart and timecheckstate together is what an attempt started at that time looks like.
     *
     * @param \stdClass $quiz
     * @param \stdClass $user
     * @param int|null $timestart
     * @return \stdClass attempt
     */
    private function start(\stdClass $quiz, \stdClass $user, ?int $timestart = null): \stdClass {
        global $DB;
        $this->setUser($user);
        $attempt = $this->quizgen->create_attempt($quiz->id, $user->id);
        if ($timestart !== null) {
            $record = $DB->get_record('quiz_attempts', ['id' => $attempt->id]);
            $delta = $timestart - $record->timestart;
            $DB->update_record('quiz_attempts', (object) [
                'id' => $attempt->id,
                'timestart' => $timestart,
                'timecheckstate' => $record->timecheckstate === null ? null : $record->timecheckstate + $delta,
            ]);
            $attempt->timestart = $timestart;
        }
        return $attempt;
    }

    /**
     * Submits an attempt (state submitted) and, by default, grades it (state finished).
     *
     * @param \stdClass $attempt
     * @param bool $grade
     */
    private function submit(\stdClass $attempt, bool $grade = true): void {
        $this->setUser($attempt->userid);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_submit(time(), false);
        if ($grade) {
            $attemptobj->process_grade_submission(time());
        }
    }

    /**
     * Whether the user may send private messages (the check every send path makes first).
     *
     * @param \stdClass $user
     * @return bool
     */
    private function can_send(\stdClass $user): bool {
        return has_capability('moodle/site:sendmessage', \context_system::instance(), $user);
    }

    /**
     * Asserts lock state through the role assignment and the effective capability.
     *
     * @param bool $expected
     * @param \stdClass $quiz
     * @param \stdClass ...$users
     */
    private function assert_locked(bool $expected, \stdClass $quiz, \stdClass ...$users): void {
        foreach ($users as $user) {
            $this->assertSame($expected, lock_manager::is_locked($quiz->id, $user->id), "assignment of user {$user->id}");
            $this->assertSame(!$expected, $this->can_send($user), "capability of user {$user->id}");
        }
    }

    /**
     * The role prohibits exactly sendmessage, cannot be assigned by hand and repairs its prohibit.
     */
    public function test_role_prohibits_sendmessage(): void {
        global $DB;
        $roleid = lock_manager::get_roleid();
        $this->assertEquals(CAP_PROHIBIT, $DB->get_field('role_capabilities', 'permission', [
            'roleid' => $roleid, 'capability' => 'moodle/site:sendmessage',
            'contextid' => \context_system::instance()->id,
        ]));
        $this->assertSame(1, $DB->count_records('role_capabilities', ['roleid' => $roleid]));
        $this->assertSame([], get_role_contextlevels($roleid));

        unassign_capability('moodle/site:sendmessage', $roleid);
        $this->assertSame($roleid, lock_manager::get_roleid());
        $this->assertTrue($DB->record_exists('role_capabilities', ['roleid' => $roleid, 'permission' => CAP_PROHIBIT]));
    }

    /**
     * Regression 1: lock disabled.
     */
    public function test_disabled_quiz_does_not_lock(): void {
        $quiz = $this->make_quiz(['messaginglockenabled' => 0, 'timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->start($quiz, $s1);
        $this->assert_locked(false, $quiz, $s1);
    }

    /**
     * Regression 2, 5, 6, 12: every send path refuses before anything is stored or delivered.
     */
    public function test_running_exam_blocks_all_send_paths_without_storing(): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2] = [$this->make_student(), $this->make_student()];
        $conv = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, [$s1->id, $s2->id]);
        $self = api::get_self_conversation($s1->id)
            ?: api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_SELF, [$s1->id]);

        $this->start($quiz, $s1);
        $this->assert_locked(true, $quiz, $s1);
        $this->assert_locked(false, $quiz, $s2);

        $sink = $this->redirectMessages();
        $before = $DB->count_records('messages');
        $this->setUser($s1);

        foreach ([$conv->id, $self->id] as $convid) {
            try {
                api::send_message_to_conversation($s1->id, $convid, 'answer is B', FORMAT_PLAIN);
                $this->fail('send_message_to_conversation must refuse');
            } catch (\moodle_exception $e) {
                $this->assertStringContainsString('cannot send a message', $e->getMessage());
            }
        }
        try {
            \core_message_external::send_messages_to_conversation($conv->id, [['text' => 'B', 'textformat' => FORMAT_PLAIN]]);
            $this->fail('core_message_send_messages_to_conversation must refuse');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('cannot send a message', $e->getMessage());
        }
        try {
            \core_message_external::send_instant_messages([['touserid' => $s2->id, 'text' => 'B', 'textformat' => FORMAT_PLAIN]]);
            $this->fail('core_message_send_instant_messages must refuse');
        } catch (\required_capability_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }

        $this->assertSame($before, $DB->count_records('messages'), 'Nothing may be stored.');
        $this->assertCount(0, $sink->get_messages(), 'Nothing may be delivered.');
    }

    /**
     * Regression 1 (positive control): without a lock the same call really sends.
     */
    public function test_unlocked_user_really_sends(): void {
        $s1 = $this->make_student();
        $s2 = $this->make_student();
        $conv = api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, [$s1->id, $s2->id]);
        $sink = $this->redirectMessages();
        $this->setUser($s1);
        api::send_message_to_conversation($s1->id, $conv->id, 'hello', FORMAT_PLAIN);
        $this->assertCount(1, $sink->get_messages());
    }

    /**
     * Paket 002 example / tests 1, 2: start 08:00, 90 min, A submits 08:30, B 08:45 -> both locked until 09:30.
     */
    public function test_early_submissions_keep_lock_until_period_end(): void {
        $quiz = $this->make_quiz(['timelimit' => 90 * MINSECS]);
        [$a, $b] = [$this->make_student(), $this->make_student()];
        $t0 = time() - 50 * MINSECS; // Now is 08:50.
        $attempta = $this->start($quiz, $a, $t0);
        $attemptb = $this->start($quiz, $b, $t0);
        $this->submit($attempta);
        $this->submit($attemptb);

        $this->assertSame(0, lock_manager::release_ended(null, time()));
        $this->assert_locked(true, $quiz, $a, $b);
        $this->assertSame(0, lock_manager::release_ended(null, $t0 + 89 * MINSECS));
        $this->assert_locked(true, $quiz, $a, $b);
        $this->assertSame(1, lock_manager::release_ended(null, $t0 + 93 * MINSECS));
        $this->assert_locked(false, $quiz, $a, $b);
    }

    /**
     * Test 10: submitted, finished and abandoned attempts do not shorten the lock period.
     */
    public function test_submitted_finished_abandoned_do_not_release(): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS, 'overduehandling' => 'autoabandon']);
        [$s1, $s2, $s3] = [$this->make_student(), $this->make_student(), $this->make_student()];
        $this->submit($this->start($quiz, $s1), false);
        $this->submit($this->start($quiz, $s2));
        $attempt3 = $this->start($quiz, $s3);
        quiz_attempt::create($attempt3->id)->process_abandon(time(), false);

        $states = $DB->get_records_menu('quiz_attempts', ['quiz' => $quiz->id], 'userid', 'userid, state');
        $this->assertSame(['submitted', 'finished', 'abandoned'], array_values($states));

        (new task\release_locks())->execute();
        $this->expectOutputRegex('/Released messaging locks of 0 quiz/');
        $this->assert_locked(true, $quiz, $s1, $s2, $s3);
    }

    /**
     * Tests 3, 4: staggered starts; the lock of everyone lasts until the latest attempt's period ends.
     */
    public function test_staggered_starts_lock_until_latest_period_end(): void {
        $quiz = $this->make_quiz(['timelimit' => 90 * MINSECS]);
        [$s1, $s2, $s3] = [$this->make_student(), $this->make_student(), $this->make_student()];
        $t0 = time() - HOURSECS;
        $this->submit($this->start($quiz, $s1, $t0));
        $this->submit($this->start($quiz, $s2, $t0 + 20 * MINSECS));
        $this->start($quiz, $s3); // Starts at t0 + 60 min after both submitted; period ends t0 + 150 min.
        $this->assert_locked(true, $quiz, $s1, $s2, $s3);

        $this->assertSame(0, lock_manager::release_ended(null, $t0 + 100 * MINSECS));
        $this->assertSame(0, lock_manager::release_ended(null, $t0 + 149 * MINSECS));
        $this->assert_locked(true, $quiz, $s1, $s2, $s3);
        $this->assertSame(1, lock_manager::release_ended(null, $t0 + 153 * MINSECS));
        $this->assert_locked(false, $quiz, $s1, $s2, $s3);
    }

    /**
     * Edge case: a start after every lock period has ended opens a new period without earlier finishers.
     */
    public function test_new_start_after_period_ended_does_not_relock(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2] = [$this->make_student(), $this->make_student()];
        $this->submit($this->start($quiz, $s1, time() - 2 * HOURSECS));
        $this->assertSame(1, lock_manager::release_ended());
        $this->assert_locked(false, $quiz, $s1);

        $this->start($quiz, $s2);
        $this->assert_locked(false, $quiz, $s1);
        $this->assert_locked(true, $quiz, $s2);
    }

    /**
     * Regression 9: user override extends the period.
     */
    public function test_user_override_extends_lock(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->quizgen->create_override(['quiz' => $quiz->id, 'userid' => $s1->id, 'timelimit' => 2 * HOURSECS]);
        $now = time();
        $this->submit($this->start($quiz, $s1));

        lock_manager::release_ended(null, $now + HOURSECS + 5 * MINSECS);
        $this->assert_locked(true, $quiz, $s1);
        lock_manager::release_ended(null, $now + 2 * HOURSECS + 5 * MINSECS);
        $this->assert_locked(false, $quiz, $s1);
    }

    /**
     * Regression 9: group override extends the period.
     */
    public function test_group_override_extends_lock(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $group = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $s1->id]);
        $this->quizgen->create_override(['quiz' => $quiz->id, 'groupid' => $group->id, 'timelimit' => 2 * HOURSECS]);
        $now = time();
        $this->submit($this->start($quiz, $s1));

        lock_manager::release_ended(null, $now + HOURSECS + 5 * MINSECS);
        $this->assert_locked(true, $quiz, $s1);
    }

    /**
     * Test 7, case J: extending and shortening the time limit during the exam apply without a page view.
     */
    public function test_time_change_during_exam_applies_immediately(): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $now = time();
        $this->start($quiz, $s1);

        // Extension by an override created after the start.
        $this->quizgen->create_override(['quiz' => $quiz->id, 'userid' => $s1->id, 'timelimit' => 2 * HOURSECS]);
        $this->assertSame(0, lock_manager::release_ended(null, $now + 90 * MINSECS));
        $this->assert_locked(true, $quiz, $s1);

        // Shortening: override reduced to 30 min, quiz limit reduced as well.
        $DB->set_field('quiz_overrides', 'timelimit', 30 * MINSECS, ['quiz' => $quiz->id, 'userid' => $s1->id]);
        $DB->set_field('quiz', 'timelimit', 30 * MINSECS, ['id' => $quiz->id]);
        $DB->set_field('quiz_attempts', 'timecheckstate', $now + 30 * MINSECS, ['quiz' => $quiz->id]);
        $this->assertSame(1, lock_manager::release_ended(null, $now + 33 * MINSECS));
        $this->assert_locked(false, $quiz, $s1);
    }

    /**
     * Grace period extends the period.
     */
    public function test_grace_period_counts(): void {
        $quiz = $this->make_quiz([
            'timelimit' => HOURSECS, 'overduehandling' => 'graceperiod', 'graceperiod' => 10 * MINSECS,
        ]);
        $s1 = $this->make_student();
        $now = time();
        $this->submit($this->start($quiz, $s1));

        $this->assertTrue(lock_manager::is_exam_running($quiz->id, $now + HOURSECS + 5 * MINSECS));
        $this->assertFalse(lock_manager::is_exam_running($quiz->id, $now + HOURSECS + 13 * MINSECS));
    }

    /**
     * Tests 5, 6 / regression 10: two locks at once; expiry of one keeps the other.
     */
    public function test_parallel_exams_are_independent(): void {
        $q1 = $this->make_quiz(['timelimit' => HOURSECS]);
        $q2 = $this->make_quiz(['timelimit' => 2 * HOURSECS]);
        $s1 = $this->make_student();
        $now = time();
        $this->start($q1, $s1);
        $this->start($q2, $s1);
        $this->assertTrue(lock_manager::is_locked($q1->id, $s1->id));
        $this->assertTrue(lock_manager::is_locked($q2->id, $s1->id));

        $this->assertSame(1, lock_manager::release_ended(null, $now + HOURSECS + 5 * MINSECS));
        $this->assertFalse(lock_manager::is_locked($q1->id, $s1->id));
        $this->assertTrue(lock_manager::is_locked($q2->id, $s1->id));
        $this->assertFalse($this->can_send($s1), 'The remaining lock still prohibits.');

        $this->assertSame(1, lock_manager::release_ended(null, $now + 2 * HOURSECS + 5 * MINSECS));
        $this->assertTrue($this->can_send($s1));
    }

    /**
     * Regression 13: teacher previews never lock.
     */
    public function test_teacher_preview_does_not_lock(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
        quiz_prepare_and_start_new_attempt(quiz_settings::create($quiz->id, $teacher->id), 1, null);
        $this->assert_locked(false, $quiz, $teacher);
    }

    /**
     * Regression 14, case H: without time limit the fallback period applies, shortened by an earlier close date.
     */
    public function test_no_time_limit_uses_fallback_or_close_date(): void {
        $quiz = $this->make_quiz();
        $s1 = $this->make_student();
        $now = time();
        $this->submit($this->start($quiz, $s1));

        lock_manager::release_ended(null, $now + 3 * HOURSECS);
        $this->assert_locked(true, $quiz, $s1);
        lock_manager::release_ended(null, $now + 4 * HOURSECS + 5 * MINSECS);
        $this->assert_locked(false, $quiz, $s1);

        $closing = $this->make_quiz(['timeclose' => $now + HOURSECS]);
        $s2 = $this->make_student();
        $this->submit($this->start($closing, $s2));
        lock_manager::release_ended(null, $now + 50 * MINSECS);
        $this->assert_locked(true, $closing, $s2);
        lock_manager::release_ended(null, $now + HOURSECS + 5 * MINSECS);
        $this->assert_locked(false, $closing, $s2);
    }

    /**
     * Regression 15, case I: deleting an attempt, disabling the option or deleting the quiz.
     */
    public function test_disable_delete_release(): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2] = [$this->make_student(), $this->make_student()];
        $a1 = $this->start($quiz, $s1);
        $this->start($quiz, $s2);

        quiz_delete_attempt($a1, $quiz);
        lock_manager::release_ended();
        $this->assert_locked(true, $quiz, $s1);

        $quiz->messaginglockenabled = 0;
        \quizaccess_messaginglock::save_settings($quiz);
        $this->assert_locked(false, $quiz, $s1, $s2);

        $quiz2 = $this->make_quiz(['timelimit' => HOURSECS]);
        $this->start($quiz2, $s1);
        $this->assert_locked(true, $quiz2, $s1);
        $this->delete_quiz($quiz2);
        lock_manager::release_ended();
        $this->assert_locked(false, $quiz2, $s1);
        $this->assertFalse($DB->record_exists('quizaccess_messaginglock', ['quizid' => $quiz2->id]));
    }

    /**
     * Test 8: the task is repeatable and removes only ended locks.
     */
    public function test_task_is_repeatable(): void {
        $ended = $this->make_quiz(['timelimit' => HOURSECS]);
        $active = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2] = [$this->make_student(), $this->make_student()];
        $this->submit($this->start($ended, $s1, time() - 2 * HOURSECS));
        $this->submit($this->start($active, $s2));

        $task = new task\release_locks();
        $task->execute();
        $task->execute();
        $this->expectOutputRegex('/locks of 1 quiz.*locks of 0 quiz/s');
        $this->assert_locked(false, $ended, $s1);
        $this->assert_locked(true, $active, $s2);
    }

    /**
     * Test 9a: if the lock cannot be set, the attempt start is rolled back (fail closed).
     */
    public function test_lock_failure_rolls_back_attempt_start(): void {
        global $DB;
        $this->preventResetByRollback();
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();

        // Make get_roleid() fail: prohibit missing and the capability unknown.
        unassign_capability(lock_manager::CAPABILITY, lock_manager::get_roleid());
        $DB->delete_records('capabilities', ['name' => lock_manager::CAPABILITY]);
        \cache::make('core', 'capabilities')->purge();

        try {
            $this->start($quiz, $s1);
            $this->fail('The attempt must not start without its lock.');
        } catch (\coding_exception $e) {
            $this->assertStringContainsString(lock_manager::CAPABILITY, $e->getMessage());
        }
        if ($DB->is_transaction_started()) {
            $DB->force_transaction_rollback();
        }
        $this->resetDebugging();

        $this->assertFalse($DB->record_exists('quiz_attempts', ['quiz' => $quiz->id, 'userid' => $s1->id]));
        $this->assertFalse(lock_manager::is_locked($quiz->id, $s1->id));
    }

    /**
     * Test 9b: a failing release keeps that quiz's locks, still releases other quizzes and reports the error.
     */
    public function test_release_failure_is_isolated(): void {
        $q1 = $this->make_quiz(['timelimit' => HOURSECS]);
        $q2 = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2] = [$this->make_student(), $this->make_student()];
        $this->submit($this->start($q1, $s1, time() - 2 * HOURSECS));
        $this->submit($this->start($q2, $s2, time() - 2 * HOURSECS));

        testable_lock_manager::$failfor = (int) $q1->id;
        try {
            testable_lock_manager::release_ended();
            $this->fail('The release error must be reported.');
        } catch (\moodle_exception $e) {
            $this->assertSame('releasefailed', $e->errorcode);
        } finally {
            testable_lock_manager::$failfor = 0;
        }
        $this->assert_locked(true, $q1, $s1);
        $this->assert_locked(false, $q2, $s2);
    }

    /**
     * Fail closed: a user inside the period who lost the lock is re-locked on the next quiz page.
     */
    public function test_prevent_access_restores_missing_lock(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->submit($this->start($quiz, $s1));
        role_unassign_all(['component' => lock_manager::COMPONENT, 'itemid' => $quiz->id]);
        $this->assert_locked(false, $quiz, $s1);

        $this->setUser($s1);
        $rule = \quizaccess_messaginglock::make(quiz_settings::create($quiz->id, $s1->id), time(), false);
        $this->assertNotNull($rule);
        $this->assertFalse($rule->prevent_access());
        $this->assert_locked(true, $quiz, $s1);
    }

    /**
     * Audit F6/F1: a reopened attempt without time limit stays locked until its (extended) close date.
     *
     * Core resets timecheckstate to NULL on reopen; catches a lock that only reads timecheckstate
     * or drops the open-attempt branch.
     */
    public function test_reopened_attempt_without_time_limit_stays_locked_until_close(): void {
        $quiz = $this->make_quiz();
        $s1 = $this->make_student();
        $now = time();
        $attempt = $this->start($quiz, $s1, $now - 5 * HOURSECS); // Older than the 4 h fallback.
        quiz_attempt::create($attempt->id)->process_abandon($now, false);
        lock_manager::release_ended(null, $now);
        $this->assert_locked(false, $quiz, $s1);

        $this->quizgen->create_override(['quiz' => $quiz->id, 'userid' => $s1->id, 'timeclose' => $now + 2 * HOURSECS]);
        quiz_attempt::create($attempt->id)->process_reopen_abandoned($now);
        $this->assert_locked(true, $quiz, $s1);

        $this->assertSame(0, lock_manager::release_ended(null, $now + HOURSECS));
        $this->assert_locked(true, $quiz, $s1);
        $this->assertSame(1, lock_manager::release_ended(null, $now + 2 * HOURSECS + 5 * MINSECS));
        $this->assert_locked(false, $quiz, $s1);
    }

    /**
     * Audit F1: an open attempt without time limit stays locked beyond the fallback until the close date.
     */
    public function test_open_attempt_without_time_limit_stays_locked_until_close(): void {
        $now = time();
        $quiz = $this->make_quiz(['timeclose' => $now + 6 * HOURSECS]);
        $s1 = $this->make_student();
        $this->start($quiz, $s1);

        lock_manager::release_ended(null, $now + 5 * HOURSECS);
        $this->assert_locked(true, $quiz, $s1);
        lock_manager::release_ended(null, $now + 6 * HOURSECS + 5 * MINSECS);
        $this->assert_locked(false, $quiz, $s1);
    }

    /**
     * Audit F5: the admin fallback is honoured, a far close date does not extend a submitted attempt,
     * and an "unlimited" override falls back as well.
     */
    public function test_fallback_respects_admin_setting_and_late_close(): void {
        $now = time();
        set_config('fallbackduration', 2 * HOURSECS, 'quizaccess_messaginglock');
        $quiz = $this->make_quiz(['timeclose' => $now + 3 * DAYSECS]);
        $s1 = $this->make_student();
        $this->submit($this->start($quiz, $s1));
        lock_manager::release_ended(null, $now + HOURSECS);
        $this->assert_locked(true, $quiz, $s1);
        lock_manager::release_ended(null, $now + 2 * HOURSECS + 5 * MINSECS);
        $this->assert_locked(false, $quiz, $s1);

        $limited = $this->make_quiz(['timelimit' => HOURSECS]);
        $s2 = $this->make_student();
        $this->quizgen->create_override(['quiz' => $limited->id, 'userid' => $s2->id, 'timelimit' => 0]);
        $this->submit($this->start($limited, $s2));
        lock_manager::release_ended(null, $now + HOURSECS + 30 * MINSECS);
        $this->assert_locked(true, $limited, $s2);
    }

    /**
     * Audit F10/S-F2: the end is exclusive and exact to the second, including core's quiz/graceperiodmin.
     */
    public function test_exam_time_boundary(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS, 'overduehandling' => 'autosubmit']);
        $t0 = time() - 10 * MINSECS;
        $this->submit($this->start($quiz, $this->make_student(), $t0));
        $this->assertSame(60, (int) get_config('quiz', 'graceperiodmin'), 'core default');
        $this->assertTrue(lock_manager::is_exam_running($quiz->id, $t0 + HOURSECS + 59));
        $this->assertFalse(lock_manager::is_exam_running($quiz->id, $t0 + HOURSECS + 60));
        set_config('graceperiodmin', 300, 'quiz');
        $this->assertTrue(lock_manager::is_exam_running($quiz->id, $t0 + HOURSECS + 299));
        $this->assertFalse(lock_manager::is_exam_running($quiz->id, $t0 + HOURSECS + 300));
    }

    /**
     * Audit F2: prevent_access only locks the user who is inside a lock period, nobody else.
     */
    public function test_prevent_access_does_not_lock_outside_the_period(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        [$old, $running, $visitor] = [$this->make_student(), $this->make_student(), $this->make_student()];
        $this->submit($this->start($quiz, $old, time() - 2 * DAYSECS));
        $this->start($quiz, $running); // The exam is active for somebody else.
        role_unassign_all(['component' => lock_manager::COMPONENT, 'itemid' => $quiz->id, 'userid' => $old->id]);

        foreach ([$old, $visitor] as $user) {
            $this->setUser($user);
            $rule = \quizaccess_messaginglock::make(quiz_settings::create($quiz->id, $user->id), time(), false);
            $this->assertFalse($rule->prevent_access());
            $this->assert_locked(false, $quiz, $user);
        }
    }

    /**
     * Audit F2: if the lock cannot be restored, prevent_access refuses access (fail closed).
     */
    public function test_prevent_access_refuses_when_lock_fails(): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->start($quiz, $s1);
        role_unassign_all(['component' => lock_manager::COMPONENT, 'itemid' => $quiz->id]);
        unassign_capability(lock_manager::CAPABILITY, lock_manager::get_roleid());
        $DB->delete_records('capabilities', ['name' => lock_manager::CAPABILITY]);
        \cache::make('core', 'capabilities')->purge();

        $this->setUser($s1);
        $rule = \quizaccess_messaginglock::make(quiz_settings::create($quiz->id, $s1->id), time(), false);
        $this->assertSame(get_string('lockfailed', 'quizaccess_messaginglock'), $rule->prevent_access());
        $this->resetDebugging();
    }

    /**
     * Audit F3: an attempt started while the release runs is locked again (re-lock after deletion).
     */
    public function test_release_relocks_attempt_started_during_release(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2] = [$this->make_student(), $this->make_student()];
        $this->submit($this->start($quiz, $s1, time() - 2 * DAYSECS));
        testable_lock_manager::$beforerelease = function () use ($quiz, $s2): void {
            $this->start($quiz, $s2);
        };
        try {
            $this->assertSame(1, testable_lock_manager::release_ended());
        } finally {
            testable_lock_manager::$beforerelease = null;
        }
        $this->assert_locked(false, $quiz, $s1);
        $this->assert_locked(true, $quiz, $s2);
    }

    /**
     * Audit F4: deleting the quiz releases its locks at once, without waiting for the task.
     */
    public function test_quiz_deletion_releases_immediately(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->start($quiz, $s1);
        $this->assert_locked(true, $quiz, $s1);
        $this->delete_quiz($quiz);
        $this->assert_locked(false, $quiz, $s1);
    }

    /**
     * Audit F7: every send path is refused during the exam and works again after the release.
     *
     * The second half proves that the refusal came from the lock, not from membership or privacy.
     *
     * @param string $path
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('send_paths')]
    public function test_send_path_refused_while_locked_and_working_after_release(string $path): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2, $s3] = [$this->make_student(), $this->make_student(), $this->make_student()];
        $conversations = [
            'individual' => api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL, [$s1->id, $s2->id]),
            'group' => api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_GROUP, [$s1->id, $s2->id, $s3->id], 'G'),
            'self' => api::get_self_conversation($s1->id)
                ?: api::create_conversation(api::MESSAGE_CONVERSATION_TYPE_SELF, [$s1->id]),
        ];
        $send = function () use ($path, $conversations, $s1, $s2): void {
            $this->setUser($s1);
            if ($path === 'ws_instant') {
                $result = \core_message_external::send_instant_messages(
                    [['touserid' => $s2->id, 'text' => 'B', 'textformat' => FORMAT_PLAIN]]
                );
                if (empty($result[0]['msgid']) || $result[0]['msgid'] < 1) {
                    throw new \moodle_exception('cannot send a message: ' . json_encode($result));
                }
            } else if ($path === 'ws_conversation') {
                \core_message_external::send_messages_to_conversation(
                    $conversations['individual']->id,
                    [['text' => 'B', 'textformat' => FORMAT_PLAIN]]
                );
            } else {
                api::send_message_to_conversation($s1->id, $conversations[$path]->id, 'B', FORMAT_PLAIN);
            }
        };
        $sink = $this->redirectMessages();
        $attempt = $this->start($quiz, $s1);
        $before = $DB->count_records('messages');
        try {
            $send();
            $this->fail("$path must refuse while locked");
        } catch (\required_capability_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('cannot send a message', $e->getMessage());
        }
        $this->assertSame($before, $DB->count_records('messages'));
        $this->assertCount(0, $sink->get_messages());

        $this->submit($attempt);
        lock_manager::release_ended(null, time() + DAYSECS);
        $this->assert_locked(false, $quiz, $s1);
        $send();
        $this->assertSame($before + 1, $DB->count_records('messages'));
    }

    /**
     * Data for test_send_path_refused_while_locked_and_working_after_release.
     *
     * @return array
     */
    public static function send_paths(): array {
        return [
            'individual conversation' => ['individual'],
            'group conversation' => ['group'],
            'self conversation' => ['self'],
            'core_message_send_messages_to_conversation' => ['ws_conversation'],
            'core_message_send_instant_messages' => ['ws_instant'],
        ];
    }

    /**
     * Audit F8: uninstalling removes the role and with it every lock.
     */
    public function test_uninstall_removes_all_locks(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/accessrule/messaginglock/db/uninstall.php');
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->start($quiz, $s1);
        $this->assert_locked(true, $quiz, $s1);

        xmldb_quizaccess_messaginglock_uninstall();
        $this->assertFalse($DB->record_exists('role', ['shortname' => lock_manager::ROLE_SHORTNAME]));
        $this->assert_locked(false, $quiz, $s1);
    }

    /**
     * Audit F10: the release task is scheduled every minute and enabled.
     */
    public function test_release_task_runs_every_minute(): void {
        $task = \core\task\manager::get_scheduled_task(task\release_locks::class);
        $this->assertNotNull($task);
        $this->assertSame('*', $task->get_minute());
        $this->assertSame('*', $task->get_hour());
        $this->assertFalse($task->get_disabled());
    }

    /**
     * Audit S-F1: one quiz that cannot be released must not make the task fail (core would back it
     * off up to a day for all quizzes); the others are released, the error is logged.
     */
    public function test_task_does_not_fail_because_of_one_quiz(): void {
        $q1 = $this->make_quiz(['timelimit' => HOURSECS]);
        $q2 = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2] = [$this->make_student(), $this->make_student()];
        $this->submit($this->start($q1, $s1, time() - 2 * DAYSECS));
        $this->submit($this->start($q2, $s2, time() - 2 * DAYSECS));

        $task = new task\release_locks();
        $task->manager = testable_lock_manager::class;
        testable_lock_manager::$failfor = (int) $q1->id;
        try {
            $task->execute();
        } finally {
            testable_lock_manager::$failfor = 0;
        }
        $this->expectOutputRegex('/failed for some quizzes.*injected release failure/s');
        $this->assertDebuggingCalled();
        $this->assert_locked(true, $q1, $s1);
        $this->assert_locked(false, $q2, $s2);
    }

    /**
     * Audit S-F4: a lock role edited during an exam (prohibit turned into allow, extra permission
     * added) is repaired by the next task run, so the running locks work again.
     */
    public function test_tampered_role_is_repaired_by_the_task(): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->start($quiz, $s1);
        $roleid = lock_manager::get_roleid();
        $sys = \context_system::instance();
        assign_capability(lock_manager::CAPABILITY, CAP_ALLOW, $roleid, $sys->id, true);
        assign_capability('moodle/site:config', CAP_ALLOW, $roleid, $sys->id, true);
        $this->assertTrue($this->can_send($s1), 'tampering is effective before the repair');

        lock_manager::release_ended();
        $this->assertFalse($this->can_send($s1));
        $this->assertSame(1, $DB->count_records('role_capabilities', ['roleid' => $roleid]));
        $this->assertFalse(has_capability('moodle/site:config', $sys, $s1));
    }

    /**
     * Audit S-F5: uninstalling after the role short name was changed still removes every lock.
     */
    public function test_uninstall_after_role_rename_removes_all_locks(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/accessrule/messaginglock/db/uninstall.php');
        $q1 = $this->make_quiz(['timelimit' => HOURSECS]);
        $q2 = $this->make_quiz(['timelimit' => HOURSECS]);
        [$s1, $s2] = [$this->make_student(), $this->make_student()];
        $this->start($q1, $s1);
        $DB->set_field('role', 'shortname', 'renamedbyadmin', ['shortname' => lock_manager::ROLE_SHORTNAME]);
        $this->start($q2, $s2); // Creates a second lock role under the expected short name.

        xmldb_quizaccess_messaginglock_uninstall();
        $this->assertSame(0, $DB->count_records('role_assignments', ['component' => lock_manager::COMPONENT]));
        $this->assertTrue($this->can_send($s1));
        $this->assertTrue($this->can_send($s2));
    }

    /**
     * Audit S-F6: saving a quiz without the form field (script, other plugin) keeps a running lock.
     */
    public function test_save_without_the_field_keeps_the_lock(): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->start($quiz, $s1);

        // Our rule directly: access_manager would also call quizaccess_seb, which needs a form's coursemodule.
        \quizaccess_messaginglock::save_settings($DB->get_record('quiz', ['id' => $quiz->id]));
        $this->assertTrue(lock_manager::is_enabled($quiz->id));
        $this->assert_locked(true, $quiz, $s1);
    }

    /**
     * Audit S-F12: a session that loaded its capabilities before the lock (second tab, same login)
     * sees the lock on its next capability check.
     */
    public function test_existing_session_sees_the_lock(): void {
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->setUser($s1);
        $sys = \context_system::instance();
        $this->assertTrue(has_capability('moodle/site:sendmessage', $sys), 'capabilities of the session are loaded');

        $this->quizgen->create_attempt($quiz->id, $s1->id); // The same session starts the attempt.
        $this->assertFalse(has_capability('moodle/site:sendmessage', $sys));
    }

    /**
     * Audit S-F12: duplicate lock assignments (concurrent starts) are all removed on release.
     */
    public function test_duplicate_assignments_are_all_released(): void {
        global $DB;
        $quiz = $this->make_quiz(['timelimit' => HOURSECS]);
        $s1 = $this->make_student();
        $this->submit($this->start($quiz, $s1, time() - 2 * DAYSECS));
        $ra = $DB->get_record('role_assignments', ['component' => lock_manager::COMPONENT, 'userid' => $s1->id]);
        unset($ra->id);
        $DB->insert_record('role_assignments', $ra);
        $this->assertSame(2, $DB->count_records('role_assignments', ['component' => lock_manager::COMPONENT]));

        lock_manager::release_ended();
        $this->assertSame(0, $DB->count_records('role_assignments', ['component' => lock_manager::COMPONENT]));
        $this->assert_locked(false, $quiz, $s1);
    }
}
