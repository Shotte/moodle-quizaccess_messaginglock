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

use PHPUnit\Framework\Attributes\CoversNothing;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Backup, restore, import and duplication keep the setting and nothing else (Paket 007).
 *
 * Every test reads the setting of the quiz that restore actually created.
 *
 * @package    quizaccess_messaginglock
 * @category   test
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class backup_restore_test extends \advanced_testcase {
    /** @var \stdClass */
    private $course;

    /** @var \mod_quiz_generator */
    private $quizgen;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $this->quizgen = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
    }

    /**
     * Creates a quiz without questions.
     *
     * @param string $name
     * @param int $enabled
     * @param \stdClass|null $course
     * @return \stdClass
     */
    private function quiz(string $name, int $enabled, ?\stdClass $course = null): \stdClass {
        return $this->quizgen->create_instance([
            'course' => ($course ?? $this->course)->id,
            'name' => $name,
            'messaginglockenabled' => $enabled,
        ]);
    }

    /**
     * Quizzes of a course by name.
     *
     * @param \stdClass|int $course
     * @return \stdClass[]
     */
    private function quizzes($course): array {
        global $DB;
        $byname = [];
        foreach ($DB->get_records('quiz', ['course' => is_object($course) ? $course->id : $course]) as $quiz) {
            $byname[$quiz->name] = $quiz;
        }
        return $byname;
    }

    /**
     * Quizzes of the test course created after the given ones (same-course restores repeat names).
     *
     * @param \stdClass ...$existing
     * @return \stdClass[]
     */
    private function new_quizzes(\stdClass ...$existing): array {
        global $DB;
        [$notin, $params] = $DB->get_in_or_equal(array_column($existing, 'id'), SQL_PARAMS_NAMED, 'q', false);
        $params['course'] = $this->course->id;
        return array_values($DB->get_records_select('quiz', "course = :course AND id $notin", $params));
    }

    /**
     * Asserts the stored setting, through the quiz access manager as the quiz form reads it.
     *
     * @param bool $expected
     * @param \stdClass $quiz
     */
    private function assert_enabled(bool $expected, \stdClass $quiz): void {
        $this->assertSame($expected, lock_manager::is_enabled($quiz->id), $quiz->name);
        $settings = \mod_quiz\access_manager::load_settings($quiz->id);
        $this->assertEquals((int) $expected, $settings['messaginglockenabled'], $quiz->name);
    }

    /**
     * Runs a backup and returns an unpacked backup id for restore.
     *
     * @param string $type backup::TYPE_1ACTIVITY or TYPE_1COURSE
     * @param int $id course module or course id
     * @param int $mode backup::MODE_GENERAL or MODE_IMPORT
     * @param bool $users include user data (general mode only)
     * @return string
     */
    private function backup(string $type, int $id, int $mode = \backup::MODE_GENERAL, bool $users = false): string {
        global $USER;
        $bc = new \backup_controller($type, $id, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO, $mode, $USER->id);
        if ($mode === \backup::MODE_GENERAL) {
            $bc->get_plan()->get_setting('users')->set_value($users);
        }
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        if ($mode !== \backup::MODE_IMPORT) {
            // General backups end as a .mbz file; unpack it the way the restore UI does.
            $file = $bc->get_results()['backup_destination'];
            $backupid = 'mlock' . $backupid;
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), make_backup_temp_directory($backupid));
        }
        $bc->destroy();
        return $backupid;
    }

    /**
     * Restores a backup.
     *
     * @param string $backupid
     * @param int $courseid
     * @param int $target backup::TARGET_*
     * @param int $mode
     */
    private function restore(string $backupid, int $courseid, int $target, int $mode = \backup::MODE_GENERAL): void {
        global $USER;
        $rc = new \restore_controller($backupid, $courseid, \backup::INTERACTIVE_NO, $mode, $USER->id, $target);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
    }

    /**
     * Duplicates a quiz the way "Duplicate" in the course does.
     *
     * @param \stdClass $quiz
     * @return \stdClass the new quiz
     */
    private function duplicate(\stdClass $quiz): \stdClass {
        global $DB;
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, 0, false, MUST_EXIST);
        if (method_exists(\core_courseformat\local\cmactions::class, 'duplicate')) {
            // Moodle 5.2+: duplicate_module() is deprecated in favour of this.
            $newcm = \core_courseformat\formatactions::cm($cm->course)->duplicate($cm->id);
        } else {
            $newcm = duplicate_module(get_course($cm->course), get_fast_modinfo($cm->course)->get_cm($cm->id));
        }
        return $DB->get_record('quiz', ['id' => $newcm->instance], '*', MUST_EXIST);
    }

    /**
     * Duplicating keeps an enabled lock enabled and a disabled one disabled; originals stay as they were.
     */
    public function test_duplicate_keeps_the_setting(): void {
        global $DB;
        $on = $this->quiz('On', 1);
        $off = $this->quiz('Off', 0);

        $oncopy = $this->duplicate($on);
        $offcopy = $this->duplicate($off);

        $this->assertNotEquals($on->id, $oncopy->id);
        $this->assert_enabled(true, $oncopy);
        $this->assert_enabled(false, $offcopy);
        $this->assert_enabled(true, $on);
        $this->assert_enabled(false, $off);
        $this->assertSame(2, $DB->count_records('quizaccess_messaginglock'));
    }

    /**
     * Activity backup without user data, restored into another course.
     */
    public function test_activity_backup_restored_into_another_course(): void {
        $on = $this->quiz('On', 1);
        $off = $this->quiz('Off', 0);
        $target = $this->getDataGenerator()->create_course();

        foreach ([$on, $off] as $quiz) {
            $backupid = $this->backup(\backup::TYPE_1ACTIVITY, $quiz->cmid);
            $this->restore($backupid, $target->id, \backup::TARGET_EXISTING_ADDING);
        }

        $restored = $this->quizzes($target);
        $this->assertCount(2, $restored);
        $this->assert_enabled(true, $restored['On']);
        $this->assert_enabled(false, $restored['Off']);
    }

    /**
     * Course backup restored as a new course: each setting lands on the right new quiz id.
     */
    public function test_course_backup_restored_as_new_course_maps_quiz_ids(): void {
        // Quizzes in another course shift the ids so that old and new ids cannot coincide.
        $this->quiz('Other', 0, $this->getDataGenerator()->create_course());
        $on = $this->quiz('On', 1);
        $off = $this->quiz('Off', 0);
        $on2 = $this->quiz('On 2', 1);

        $backupid = $this->backup(\backup::TYPE_1COURSE, $this->course->id);
        $newcourseid = \restore_dbops::create_new_course('Restored', 'restored', $this->course->category);
        $this->restore($backupid, $newcourseid, \backup::TARGET_NEW_COURSE);

        $restored = $this->quizzes($newcourseid);
        $this->assertCount(3, $restored);
        $this->assert_enabled(true, $restored['On']);
        $this->assert_enabled(false, $restored['Off']);
        $this->assert_enabled(true, $restored['On 2']);
        $this->assertNotEquals($on->id, $restored['On']->id);
        $this->assert_enabled(true, $on);
        $this->assert_enabled(false, $off);
        $this->assert_enabled(true, $on2);
    }

    /**
     * Course import (MODE_IMPORT) into an existing course.
     */
    public function test_course_import(): void {
        $this->quiz('On', 1);
        $this->quiz('Off', 0);
        $target = $this->getDataGenerator()->create_course();
        $this->quiz('Existing', 0, $target);

        $backupid = $this->backup(\backup::TYPE_1COURSE, $this->course->id, \backup::MODE_IMPORT);
        $this->restore($backupid, $target->id, \backup::TARGET_EXISTING_ADDING, \backup::MODE_IMPORT);

        $restored = $this->quizzes($target);
        $this->assert_enabled(true, $restored['On']);
        $this->assert_enabled(false, $restored['Off']);
        $this->assert_enabled(false, $restored['Existing']);
    }

    /**
     * Restoring a quiz backup into its own course creates a new quiz and leaves existing quizzes alone,
     * even if their setting changed after the backup.
     */
    public function test_restore_never_changes_existing_quizzes(): void {
        global $DB;
        $quiz = $this->quiz('Exam', 1);
        $other = $this->quiz('Other', 0);
        $backupid = $this->backup(\backup::TYPE_1ACTIVITY, $quiz->cmid);
        // Disabled after the backup was made.
        $DB->delete_records('quizaccess_messaginglock', ['quizid' => $quiz->id]);

        $this->restore($backupid, $this->course->id, \backup::TARGET_CURRENT_ADDING);

        $this->assert_enabled(false, $quiz);
        $this->assert_enabled(false, $other);
        $new = $this->new_quizzes($quiz, $other);
        $this->assertCount(1, $new);
        $this->assert_enabled(true, $new[0]);
    }

    /**
     * A backup without the plugin's element (made before Paket 007 or on a site without the plugin)
     * restores without error and leaves the lock disabled: a missing record never enables it.
     */
    public function test_backup_without_plugin_record_restores_disabled(): void {
        global $CFG;
        $quiz = $this->quiz('Old', 1);
        $backupid = $this->backup(\backup::TYPE_1ACTIVITY, $quiz->cmid, \backup::MODE_IMPORT);

        $xmlfile = $CFG->backuptempdir . "/$backupid/activities/quiz_{$quiz->cmid}/quiz.xml";
        $xml = file_get_contents($xmlfile);
        $tag = 'subplugin_quizaccess_messaginglock_quiz';
        $this->assertStringContainsString('<enabled>1</enabled>', $xml, 'the backup holds the setting');
        $stripped = preg_replace("#<$tag>.*?</$tag>#s", '', $xml, -1, $count);
        $this->assertSame(1, $count);
        file_put_contents($xmlfile, $stripped);

        $this->restore($backupid, $this->course->id, \backup::TARGET_CURRENT_ADDING, \backup::MODE_IMPORT);

        $new = $this->new_quizzes($quiz);
        $this->assertCount(1, $new);
        $this->assert_enabled(false, $new[0]);
    }

    /**
     * A disabled quiz writes no element at all, so nothing personal or lock-related is in the backup.
     */
    public function test_disabled_quiz_writes_no_element(): void {
        global $CFG;
        $quiz = $this->quiz('Off', 0);
        $backupid = $this->backup(\backup::TYPE_1ACTIVITY, $quiz->cmid, \backup::MODE_IMPORT);
        $xml = file_get_contents($CFG->backuptempdir . "/$backupid/activities/quiz_{$quiz->cmid}/quiz.xml");
        $this->assertStringNotContainsString('<quizaccess_messaginglock>', $xml);
    }

    /**
     * Course backup with user data while a student is locked: restore copies the setting and the
     * attempt, but no lock. The lock on the target arises only from an attempt started there.
     */
    public function test_restore_with_user_data_copies_no_locks(): void {
        global $DB;
        $quiz = $this->quizgen->create_test_quiz([['TF1', 1, 'truefalse']], [
            'course' => $this->course->id,
            'name' => 'Exam',
            'messaginglockenabled' => 1,
        ])->get_quiz();
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);
        $this->quizgen->create_attempt($quiz->id, $student->id);
        $this->setAdminUser();
        $this->assertTrue(lock_manager::is_locked($quiz->id, $student->id));
        $component = ['component' => lock_manager::COMPONENT];
        $this->assertSame(1, $DB->count_records('role_assignments', $component));

        $backupid = $this->backup(\backup::TYPE_1COURSE, $this->course->id, \backup::MODE_GENERAL, true);
        $newcourseid = \restore_dbops::create_new_course('Restored', 'restored', $this->course->category);
        $this->restore($backupid, $newcourseid, \backup::TARGET_NEW_COURSE);

        $restored = $this->quizzes($newcourseid)['Exam'];
        $this->assert_enabled(true, $restored);
        $this->assertTrue($DB->record_exists('quiz_attempts', ['quiz' => $restored->id, 'userid' => $student->id]));
        $this->assertSame(1, $DB->count_records('role_assignments', $component), 'restore creates no lock');
        $this->assertFalse(lock_manager::is_locked($restored->id, $student->id));
        $this->assertTrue(lock_manager::is_locked($quiz->id, $student->id), 'source lock unchanged');

        // The restored setting works on the target: a new attempt there locks.
        $newstudent = $this->getDataGenerator()->create_and_enrol(get_course($newcourseid), 'student');
        $this->setUser($newstudent);
        $this->quizgen->create_attempt($restored->id, $newstudent->id);
        $this->assertTrue(lock_manager::is_locked($restored->id, $newstudent->id));
    }
}
