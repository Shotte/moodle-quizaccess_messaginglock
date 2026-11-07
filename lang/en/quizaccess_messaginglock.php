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
 * English strings for quizaccess_messaginglock.
 *
 * @package    quizaccess_messaginglock
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fallbackduration'] = 'Lock period without time limit';
$string['fallbackduration_desc'] = 'Lock period of an attempt in a quiz without a time limit, counted from the start of the attempt. An earlier close date shortens it. While the attempt is still open, the lock remains until the close date.';
$string['lockfailed'] = 'Moodle messaging could not be locked for your attempt, so you cannot work on the quiz at the moment. Please contact your teacher.';
$string['messaginglockenabled'] = 'Lock Moodle messaging during the exam';
$string['messaginglockenabled_help'] = 'If enabled, students cannot send Moodle messages during the exam time. The lock stays in place after an early submission and is lifted automatically once the exam time has ended for everyone. Other Moodle features are not affected.

In detail:

* The lock starts for each person when they start their attempt. Sending is locked in all Moodle conversations, including groups. Receiving remains possible.
* Exam time of an attempt: start of the attempt plus the time limit (including extensions and grace period). All students who have started stay locked until the latest exam time has ended. The lock is then lifted within about one minute.
* Anyone starting only after all exam times have ended is locked on their own. Earlier finishers are not locked again.
* Without a time limit, a lock period set by the administrator applies from the start of the attempt (4 hours by default). An earlier close date shortens it; an attempt that is still open extends it up to the close date. The setting is therefore recommended for quizzes with a time limit.
* The setting is kept when the quiz is duplicated, backed up and restored, or imported. Existing locks are never copied.';
$string['pluginname'] = 'Messaging lock quiz access rule';
$string['privacy:metadata'] = 'The plugin stores quiz settings only. Messaging locks are role assignments, which are covered by the role subsystem.';
$string['releasefailed'] = 'Releasing messaging locks failed: {$a}';
$string['roledescription'] = 'Assigned automatically by the quiz messaging lock while an exam is running. Do not assign or edit manually.';
$string['rolename'] = 'Quiz messaging lock';
$string['studentinfo'] = 'From the start of your attempt you cannot send Moodle messages. This continues after you submit, until the exam has ended for everyone.';
$string['taskreleaselocks'] = 'Release quiz messaging locks';
$string['taskreleaselocksdone'] = 'Released messaging locks of {$a} quiz(zes).';
$string['taskreleaselocksfailed'] = 'Releasing messaging locks failed for some quizzes, retried in the next run: {$a}';
