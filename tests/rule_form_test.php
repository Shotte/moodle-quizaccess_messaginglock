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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/mod_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/messaginglock/rule.php');

/**
 * Regression tests for the quiz form field (Paket 005): No/Yes select, "Yes" for new quizzes only,
 * stored and restored correctly, existing quizzes unchanged.
 *
 * @package    quizaccess_messaginglock
 * @category   test
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\quizaccess_messaginglock::class)]
final class rule_form_test extends \advanced_testcase {
    /**
     * Builds a form containing only this rule's field, the way mod_quiz_mod_form adds it.
     *
     * @return \MoodleQuickForm
     */
    private function build_form(): \MoodleQuickForm {
        $mform = new \MoodleQuickForm('quizsettings', 'post', '');
        \quizaccess_messaginglock::add_settings_form_fields($this->createMock(\mod_quiz_mod_form::class), $mform);
        return $mform;
    }

    /**
     * Visible text of the currently selected option.
     *
     * @param \MoodleQuickForm $mform
     * @return string
     */
    private function selected_label(\MoodleQuickForm $mform): string {
        $element = $mform->getElement('messaginglockenabled');
        $selected = array_map('strval', (array) $element->getSelected());
        foreach ($element->_options as $option) {
            if (in_array((string) $option['attr']['value'], $selected, true)) {
                return $option['text'];
            }
        }
        return '';
    }

    /**
     * Creates a quiz with the given stored setting.
     *
     * @param int $enabled
     * @return \stdClass
     */
    private function make_quiz(int $enabled): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->get_plugin_generator('mod_quiz')
            ->create_instance(['course' => $course->id, 'messaginglockenabled' => $enabled]);
    }

    /**
     * Builds the real quiz settings form exactly as course/modedit.php does and returns its QuickForm.
     *
     * @param \stdClass $course
     * @param \stdClass|null $quiz null = "add a new quiz", otherwise "edit this quiz"
     * @return \MoodleQuickForm
     */
    private function real_quiz_form(\stdClass $course, ?\stdClass $quiz): \MoodleQuickForm {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/course/modlib.php');
        // As require_login() in modedit.php: page course (sets $COURSE too), freshly loaded so modinfo knows new quizzes.
        $course = get_course($course->id);
        $PAGE = new \moodle_page();
        $PAGE->set_course($course);
        if ($quiz === null) {
            [, , $cw, $cm, $data] = prepare_new_moduleinfo_data($course, 'quiz', 0);
        } else {
            [$cm, , , $data, $cw] = get_moduleinfo_data(get_coursemodule_from_instance('quiz', $quiz->id), $course);
        }
        $form = new \mod_quiz_mod_form($data, $cw->section, $cm, $course);
        $form->set_data($data);
        return (new \ReflectionProperty(\moodleform::class, '_form'))->getValue($form);
    }

    /**
     * New quizzes default to "Yes"; existing quizzes show what is stored, "No" when nothing is stored.
     */
    public function test_default_yes_only_for_new_quizzes(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $this->assertSame(get_string('yes'), $this->selected_label($this->real_quiz_form($course, null)), 'new quiz');

        // Existing quiz without plugin row (e.g. created before the plugin or with "No"): stays "No".
        $without = $generator->create_instance(['course' => $course->id]);
        $this->assertFalse($DB->record_exists('quizaccess_messaginglock', ['quizid' => $without->id]));
        $this->assertSame(get_string('no'), $this->selected_label($this->real_quiz_form($course, $without)), 'existing, no row');

        $with = $generator->create_instance(['course' => $course->id, 'messaginglockenabled' => 1]);
        $this->assertSame(get_string('yes'), $this->selected_label($this->real_quiz_form($course, $with)), 'existing, row');

        // A new quiz saved with "No" is stored as disabled and reopens as "No".
        $no = $generator->create_instance(['course' => $course->id, 'messaginglockenabled' => '0']);
        $this->assertFalse(lock_manager::is_enabled($no->id));
        $this->assertSame(get_string('no'), $this->selected_label($this->real_quiz_form($course, $no)), 'saved No');
    }

    /**
     * The field is a core No/Yes select with help icon, defaulting to Yes.
     */
    public function test_field_is_yes_no_select_defaulting_to_yes(): void {
        $mform = $this->build_form();
        $element = $mform->getElement('messaginglockenabled');

        $this->assertSame('selectyesno', $element->getType());
        $this->assertSame(get_string('messaginglockenabled', 'quizaccess_messaginglock'), $element->getLabel());
        $options = [];
        foreach ($element->_options as $option) {
            $options[$option['attr']['value']] = $option['text'];
        }
        $this->assertSame([0 => get_string('no'), 1 => get_string('yes')], $options);
        $this->assertNotEmpty($element->_helpbutton, 'help icon kept');
        $this->assertSame(get_string('yes'), $this->selected_label($mform));
    }

    /**
     * The help icon is Moodle's own popover: keyboard focusable and carrying the help text.
     */
    public function test_help_icon_is_core_popover_with_text(): void {
        $help = $this->build_form()->getElement('messaginglockenabled')->_helpbutton;

        $this->assertStringContainsString('data-bs-toggle="popover"', $help);
        $this->assertStringContainsString('role="button"', $help);
        $this->assertStringContainsString('tabindex="0"', $help);
        $this->assertStringContainsString('early submission', $help);

        // Read the files directly: the CI site has no German language pack, so the string manager would fall back to English.
        // German is no longer shipped; it is kept in tests/fixtures/lang for AMOS.
        $string = [];
        include(__DIR__ . '/fixtures/lang/de/quizaccess_messaginglock.php');
        $de = $string;
        $string = [];
        include(__DIR__ . '/../lang/en/quizaccess_messaginglock.php');
        $en = $string;
        $this->assertSame(array_keys($en), array_keys($de), 'German and English define the same strings');
        $this->assertStringContainsString('vorzeitigen Testabgabe', $de['messaginglockenabled_help']);
    }

    /**
     * Submitted "No"/"Yes" is stored and shown again as the same choice.
     *
     * @param string $submitted value as posted by the select
     * @param bool $enabled expected stored state
     * @param string $label expected visible choice after reload
     */
    #[DataProvider('choices')]
    public function test_choice_is_saved_and_restored(string $submitted, bool $enabled, string $label): void {
        $this->resetAfterTest();
        $quiz = $this->make_quiz($enabled ? 0 : 1); // Start from the opposite state.

        $quiz->messaginglockenabled = $submitted;
        \quizaccess_messaginglock::save_settings($quiz);
        $this->assertSame($enabled, lock_manager::is_enabled($quiz->id));

        $mform = $this->build_form();
        $mform->setDefaults(\mod_quiz\access_manager::load_settings($quiz->id));
        $this->assertSame(get_string($label), $this->selected_label($mform));
    }

    /**
     * Data for test_choice_is_saved_and_restored.
     *
     * @return array
     */
    public static function choices(): array {
        return [
            'Nein' => ['0', false, 'no'],
            'Ja' => ['1', true, 'yes'],
        ];
    }

    /**
     * An activation stored by version 2026110500 (checkbox) is shown as "Yes" after the update.
     */
    public function test_existing_activation_shows_yes(): void {
        global $DB;
        $this->resetAfterTest();
        $quiz = $this->make_quiz(0);
        $DB->insert_record('quizaccess_messaginglock', (object) ['quizid' => $quiz->id]);

        $mform = $this->build_form();
        $mform->setDefaults(\mod_quiz\access_manager::load_settings($quiz->id));
        $this->assertSame(get_string('yes'), $this->selected_label($mform));
    }
}
