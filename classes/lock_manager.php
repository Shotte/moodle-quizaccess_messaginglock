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

use core\clock;
use core\di;

/**
 * All locking logic: a lock is a component role assignment (itemid = quiz id) of a role
 * that prohibits moodle/site:sendmessage in the system context.
 *
 * Time rule (docs/architecture.md): every attempt has a lock period that ends at
 * timestart + effective time limit (+ grace period), whatever state the attempt is in.
 * The exam of a quiz is active while at least one attempt's lock period has not ended;
 * all users locked for that quiz stay locked until then.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lock_manager {
    /** @var string Short name of the lock role and component of its assignments. */
    const ROLE_SHORTNAME = 'quizaccess_messaginglock';

    /** @var string Component recorded on every lock assignment. */
    const COMPONENT = 'quizaccess_messaginglock';

    /** @var string The capability the lock role prohibits. */
    const CAPABILITY = 'moodle/site:sendmessage';

    /** @var int Default lock period of attempts without a time limit. */
    const DEFAULT_FALLBACK_DURATION = 4 * HOURSECS;

    /**
     * Returns the lock role, creating it or restoring its definition when necessary.
     *
     * The role must hold exactly one capability row: sendmessage = PROHIBIT in the system context.
     * Anything else (prohibit removed, extra permissions added by an admin) is repaired.
     *
     * @return int role id
     */
    public static function get_roleid(): int {
        global $CFG, $DB;

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => self::ROLE_SHORTNAME]);
        if (!$roleid) {
            // Role names are stored as text and never translated by core (role_get_name()), so they
            // are written in the site default language, not in the language of whoever triggers this.
            $roleid = create_role(
                get_string_manager()->get_string('rolename', 'quizaccess_messaginglock', null, $CFG->lang),
                self::ROLE_SHORTNAME,
                get_string_manager()->get_string('roledescription', 'quizaccess_messaginglock', null, $CFG->lang)
            );
            // No context levels: the role cannot be assigned by hand.
            set_role_contextlevels($roleid, []);
        }

        $syscontext = \context_system::instance();
        $rows = $DB->get_records('role_capabilities', ['roleid' => $roleid]);
        $row = reset($rows);
        $intact = count($rows) === 1 && $row->capability === self::CAPABILITY
            && (int) $row->permission === CAP_PROHIBIT && (int) $row->contextid === (int) $syscontext->id;
        // Only written when broken: an unconditional write would mark the system context dirty on every call.
        if (!$intact) {
            foreach ($rows as $other) {
                unassign_capability($other->capability, $roleid, $other->contextid);
            }
            assign_capability(self::CAPABILITY, CAP_PROHIBIT, $roleid, $syscontext->id, true);
        }
        return $roleid;
    }

    /**
     * Whether the messaging lock is enabled for a quiz.
     *
     * @param int $quizid
     * @return bool
     */
    public static function is_enabled(int $quizid): bool {
        global $DB;
        return $DB->record_exists('quizaccess_messaginglock', ['quizid' => $quizid]);
    }

    /**
     * Locks a user for a quiz. Idempotent. Throws on failure, which callers rely on to fail closed.
     *
     * @param int $quizid
     * @param int $userid
     */
    public static function lock(int $quizid, int $userid): void {
        role_assign(self::get_roleid(), $userid, \context_system::instance()->id, self::COMPONENT, $quizid);
    }

    /**
     * Whether a user holds a lock for the given quiz.
     *
     * @param int $quizid
     * @param int $userid
     * @return bool
     */
    public static function is_locked(int $quizid, int $userid): bool {
        global $DB;
        return $DB->record_exists('role_assignments', [
            'contextid' => \context_system::instance()->id,
            'userid' => $userid,
            'component' => self::COMPONENT,
            'itemid' => $quizid,
        ]);
    }

    /**
     * Whether the exam is active: lock enabled and at least one attempt whose lock period has not ended.
     *
     * @param int $quizid
     * @param int|null $now
     * @return bool
     */
    public static function is_exam_running(int $quizid, ?int $now = null): bool {
        return self::is_enabled($quizid) && self::active_userids($quizid, $now) !== [];
    }

    /**
     * Releases all locks of every quiz (or one quiz) whose exam is no longer active.
     *
     * Every quiz is handled on its own: an error with one quiz neither stops the others
     * nor removes anything else. Errors are collected and rethrown at the end. Safe to repeat.
     * A full run (no quiz given, i.e. the scheduled task) first repairs the lock role, so a
     * role edited by an admin during an exam is effective again within a minute.
     *
     * @param int|null $quizid restrict to this quiz, null for all quizzes holding locks
     * @param int|null $now
     * @return int number of quizzes released
     */
    public static function release_ended(?int $quizid = null, ?int $now = null): int {
        global $DB;

        $now = $now ?? di::get(clock::class)->time();
        if ($quizid === null) {
            self::get_roleid();
        }
        $params = ['component' => self::COMPONENT, 'contextid' => \context_system::instance()->id];
        $where = '';
        if ($quizid !== null) {
            $where = 'AND itemid = :quizid';
            $params['quizid'] = $quizid;
        }
        $quizids = $DB->get_fieldset_sql(
            "SELECT DISTINCT itemid
               FROM {role_assignments}
              WHERE component = :component AND contextid = :contextid $where",
            $params
        );

        $released = 0;
        $errors = [];
        foreach ($quizids as $id) {
            try {
                if (!static::is_exam_running((int) $id, $now)) {
                    static::release_quiz((int) $id, $now);
                    $released++;
                }
            } catch (\Throwable $e) {
                $errors[] = "#{$id}: " . $e->getMessage();
            }
        }
        if ($errors) {
            throw new \moodle_exception('releasefailed', 'quizaccess_messaginglock', '', implode('; ', $errors));
        }
        return $released;
    }

    /**
     * Removes every lock of one quiz, then re-locks users whose attempt became active meanwhile.
     *
     * Race with a concurrent attempt start (separate transaction): if it committed before the
     * deletion read its row, the re-lock below sees its attempt; if it committed later, its row
     * was not read and survives. Lock assignments of other quizzes are never touched.
     *
     * @param int $quizid
     * @param int $now
     */
    protected static function release_quiz(int $quizid, int $now): void {
        role_unassign_all([
            'contextid' => \context_system::instance()->id,
            'component' => self::COMPONENT,
            'itemid' => $quizid,
        ]);
        if (self::is_enabled($quizid)) {
            foreach (self::active_userids($quizid, $now) as $userid) {
                self::lock($quizid, $userid);
            }
        }
    }

    /**
     * Users whose lock period for this quiz has not ended at $now.
     *
     * Lock period end of an attempt, independent of its state (submit/finish/abandon do not shorten it):
     * - time limit set:     timestart + effective time limit
     * - no limit, closes before timestart + fallback: effective close date
     * - otherwise:          timestart + fallback duration
     * plus the grace period if overdue handling is "graceperiod", plus quiz/graceperiodmin (core
     * still accepts answers that long after the end). Without a time limit, an attempt
     * that is still open also stays active until its effective close date (+ grace period). This is
     * read live instead of from timecheckstate, which core resets to NULL when it reopens an
     * attempt; with a time limit timecheckstate never exceeds the end above. Effective values
     * include user and group overrides and are read live, so changes during an exam apply at once.
     *
     * @param int $quizid
     * @param int|null $now
     * @param int|null $userid restrict to one user
     * @return int[]
     */
    public static function active_userids(int $quizid, ?int $now = null, ?int $userid = null): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $now = $now ?? di::get(clock::class)->time();
        $fallback = (int) get_config('quizaccess_messaginglock', 'fallbackduration');
        if ($fallback <= 0) {
            $fallback = self::DEFAULT_FALLBACK_DURATION;
        }

        $graceperiodmin = max(0, (int) get_config('quiz', 'graceperiodmin'));
        $params = [
            'uquizid' => $quizid,
            'quizid' => $quizid,
            'fallback1' => $fallback,
            'fallback2' => $fallback,
            'now1' => $now - $graceperiodmin,
            'now2' => $now - $graceperiodmin,
        ];
        $userwhere = '';
        $uuserwhere = '';
        if ($userid !== null) {
            $userwhere = 'AND qa.userid = :userid';
            $uuserwhere = 'AND iquiza.userid = :uuserid';
            $params['userid'] = $userid;
            $params['uuserid'] = $userid;
        }
        // Core helper: effective timeclose/timelimit per attempt with user and group overrides.
        $usertimesql = quiz_get_attempt_usertime_sql("iquiza.quiz = :uquizid AND iquiza.preview = 0 $uuserwhere");

        $sql = "SELECT DISTINCT qa.userid
                  FROM {quiz_attempts} qa
                  JOIN {quiz} q ON q.id = qa.quiz
                  JOIN ($usertimesql) ut ON ut.id = qa.id
                 WHERE qa.quiz = :quizid $userwhere
                   AND qa.preview = 0
                   AND qa.state <> 'notstarted'
                   AND (
                        CASE WHEN ut.usertimelimit > 0 THEN qa.timestart + ut.usertimelimit
                             WHEN ut.usertimeclose > 0 AND ut.usertimeclose < qa.timestart + :fallback1
                                  THEN ut.usertimeclose
                             ELSE qa.timestart + :fallback2 END
                        + CASE WHEN q.overduehandling = 'graceperiod' THEN q.graceperiod ELSE 0 END > :now1
                     OR (qa.state IN ('inprogress', 'overdue') AND ut.usertimelimit = 0 AND ut.usertimeclose > 0
                         AND ut.usertimeclose
                             + CASE WHEN q.overduehandling = 'graceperiod' THEN q.graceperiod ELSE 0 END > :now2)
                   )";
        return array_map('intval', $DB->get_fieldset_sql($sql, $params));
    }
}
