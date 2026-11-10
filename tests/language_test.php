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
require_once($CFG->dirroot . '/mod/quiz/mod_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/messaginglock/rule.php');

/**
 * Language integration: only English ships with the plugin, the kept translations are complete
 * and consistent, every used string is defined and every defined string used, and translations
 * delivered by a Moodle language pack (AMOS) are served through Moodle's normal language choice.
 *
 * The translations prepared up to 1.0.1 are kept in tests/fixtures/lang for the AMOS submission.
 * The resolution tests install one of them like a language pack in the test dataroot (a minimal
 * langconfig.php plus the plugin's string file). Moodle then serves those strings and falls back
 * to English for core strings such as Yes/No - exactly Moodle's fallback for a missing translation.
 *
 * @package    quizaccess_messaginglock
 * @category   test
 * @copyright  2026 Shotte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class language_test extends \advanced_testcase {
    /** @var string Component. */
    private const COMPONENT = 'quizaccess_messaginglock';

    /** @var string Kept translations (not shipped), one folder per language like a language pack. */
    private const TRANSLATIONS = __DIR__ . '/fixtures/lang';

    /**
     * Reads a string file directly: English from the plugin, other languages from the kept translations.
     *
     * @param string $lang
     * @return array
     */
    private function strings(string $lang): array {
        $string = [];
        include(($lang === 'en' ? __DIR__ . '/../lang' : self::TRANSLATIONS) . "/$lang/" . self::COMPONENT . '.php');
        return $string;
    }

    /**
     * Kept translations: every folder in tests/fixtures/lang.
     *
     * @return string[]
     */
    private function translations(): array {
        $langs = array_values(array_diff(scandir(self::TRANSLATIONS), ['.', '..']));
        $this->assertContains('de', $langs);
        return $langs;
    }

    /**
     * Installs a language like a Moodle language pack from AMOS for this test: langconfig.php plus
     * the plugin's translated string file in the dataroot.
     *
     * @param string $lang
     */
    private function register_language(string $lang): void {
        global $CFG;
        $dir = $CFG->dataroot . '/lang/' . $lang;
        if (!is_dir($dir)) {
            mkdir($dir, $CFG->directorypermissions, true);
        }
        $langconfig = "<?php\n\$string['thislanguage'] = '$lang';\n\$string['parentlanguage'] = '';\n";
        file_put_contents($dir . '/langconfig.php', $langconfig);
        copy(self::TRANSLATIONS . "/$lang/" . self::COMPONENT . '.php', "$dir/" . self::COMPONENT . '.php');
        $this->reset_language_caches();
        $this->assertTrue(get_string_manager()->translation_exists($lang, false));
    }

    /**
     * Forgets every cached list of installed languages, without touching the database.
     *
     * The list lives in the MUC cache core/langmenu (not cleared by a new string manager) and in
     * the static property cache of core_user, which validates the language of new users.
     */
    private function reset_language_caches(): void {
        get_string_manager(true)->reset_caches(true);
        \core_user::reset_caches();
    }

    /**
     * Registers German like an installed language pack for this test.
     */
    private function register_german(): void {
        $this->register_language('de');
    }

    protected function tearDown(): void {
        global $CFG;
        foreach ($this->translations() as $lang) {
            @unlink($CFG->dataroot . '/lang/' . $lang . '/langconfig.php');
            @unlink($CFG->dataroot . '/lang/' . $lang . '/' . self::COMPONENT . '.php');
        }
        $this->reset_language_caches();
        parent::tearDown();
    }

    /**
     * Only English strings ship with the plugin (Moodle Marketplace rule); translations come from AMOS.
     */
    public function test_only_english_is_shipped(): void {
        $this->assertSame(['en'], array_values(array_diff(scandir(__DIR__ . '/../lang'), ['.', '..'])));
    }

    /**
     * Every translation defines the same keys as English, in Moodle's sorted order, with the same
     * placeholders and help-text bullets, none empty.
     */
    public function test_language_files_complete_and_consistent(): void {
        $en = $this->strings('en');
        $sorted = array_keys($en);
        sort($sorted);
        $this->assertSame($sorted, array_keys($en), 'keys in alphabetical order (Moodle convention)');
        foreach ($this->translations() as $lang) {
            $tr = $this->strings($lang);
            $this->assertSame(array_keys($en), array_keys($tr), "keys of $lang");
            foreach ($en as $key => $text) {
                $this->assertNotSame('', trim($text), "en/$key");
                $this->assertNotSame('', trim($tr[$key]), "$lang/$key");
                preg_match_all('/\{\$a(->\w+)?\}/', $text, $enph);
                preg_match_all('/\{\$a(->\w+)?\}/', $tr[$key], $trph);
                $this->assertSame($enph[0], $trph[0], "placeholders of $lang/$key");
                $this->assertSame(substr_count($text, "\n* "), substr_count($tr[$key], "\n* "), "bullets of $lang/$key");
            }
        }
    }

    /**
     * Each kept translation, installed like a language pack, is served through Moodle's normal
     * language choice, and saving a setting in that language stores the same value.
     */
    public function test_every_translation_resolves(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        foreach ($this->translations() as $lang) {
            $this->register_language($lang);
            $this->setUser($this->getDataGenerator()->create_user(['lang' => $lang]));
            $this->assertSame($lang, current_language());
            $tr = $this->strings($lang);
            foreach (['messaginglockenabled', 'messaginglockenabled_help', 'studentinfo', 'lockfailed'] as $key) {
                $this->assertSame($tr[$key], get_string($key, self::COMPONENT), "$lang/$key");
            }
            $expected = str_replace('{$a}', '3', $tr['taskreleaselocksdone']);
            $this->assertSame($expected, get_string('taskreleaselocksdone', self::COMPONENT, 3), "$lang placeholder");
            $quiz = $this->getDataGenerator()->get_plugin_generator('mod_quiz')
                ->create_instance(['course' => $course->id, 'messaginglockenabled' => 1]);
            $this->assertTrue(lock_manager::is_enabled($quiz->id), $lang);
        }
    }

    /**
     * Every string the code asks for exists, and every defined string is used.
     *
     * Implicit uses: pluginname (plugin list), privacy:metadata (privacy provider), *_help (help buttons).
     */
    public function test_used_and_defined_strings_match(): void {
        $c = preg_quote(self::COMPONENT, '/');
        $used = [];
        $root = dirname(__DIR__);
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $rel = strtr(substr($file->getPathname(), strlen($root) + 1), DIRECTORY_SEPARATOR, '/');
            if ($file->getExtension() !== 'php' || preg_match('#^(tests|lang|tools|\.forgejo)/#', $rel)) {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            preg_match_all("/get_string\(\s*'([^']+)'\s*,\s*'$c'/", $src, $m);
            $used = array_merge($used, $m[1]);
            preg_match_all("/addHelpButton\(\s*'[^']+'\s*,\s*'([^']+)'\s*,\s*'$c'/", $src, $m);
            foreach ($m[1] as $id) {
                $used[] = $id;
                $used[] = $id . '_help';
            }
            preg_match_all("/moodle_exception\(\s*'([^']+)'\s*,\s*'$c'/", $src, $m);
            $used = array_merge($used, $m[1]);
            preg_match_all("/return\s+'([^']+)';/", $src, $m); // Privacy provider reason.
            $used = array_merge($used, array_filter($m[1], fn($id) => str_starts_with($id, 'privacy:')));
        }
        $used[] = 'pluginname';
        $used = array_values(array_unique($used));
        sort($used);
        $defined = array_keys($this->strings('en'));

        $this->assertSame([], array_values(array_diff($used, $defined)), 'used but not defined');
        $this->assertSame([], array_values(array_diff($defined, $used)), 'defined but never used');
    }

    /**
     * The user's language decides, not the site default; switching needs no plugin setting.
     */
    public function test_user_language_decides(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->register_german();
        $de = $this->strings('de');
        $en = $this->strings('en');
        $CFG->lang = 'en';

        $this->setUser($this->getDataGenerator()->create_user(['lang' => 'de']));
        $this->assertSame('de', current_language());
        $this->assertSame($de['messaginglockenabled'], get_string('messaginglockenabled', self::COMPONENT));
        $this->assertSame($de['studentinfo'], get_string('studentinfo', self::COMPONENT));

        $this->setUser($this->getDataGenerator()->create_user(['lang' => 'en']));
        $this->assertSame('en', current_language());
        $this->assertSame($en['messaginglockenabled'], get_string('messaginglockenabled', self::COMPONENT));

        // German site default, English user: the user preference wins.
        $CFG->lang = 'de';
        $this->assertSame($en['lockfailed'], get_string('lockfailed', self::COMPONENT));
    }

    /**
     * Missing translations fall back to English the normal Moodle way; Yes/No are core strings.
     */
    public function test_fallback_and_core_yes_no(): void {
        $this->resetAfterTest();
        $this->register_german();
        $this->setUser($this->getDataGenerator()->create_user(['lang' => 'de']));

        $mform = new \MoodleQuickForm('quizsettings', 'post', '');
        \quizaccess_messaginglock::add_settings_form_fields($this->createMock(\mod_quiz_mod_form::class), $mform);
        $element = $mform->getElement('messaginglockenabled');
        $this->assertSame($this->strings('de')['messaginglockenabled'], $element->getLabel());
        $options = array_column($element->_options, 'text');
        // No German core pack on the CI: core yes/no fall back to English, served by core, not the plugin.
        $sm = get_string_manager();
        $this->assertSame([$sm->get_string('no', 'moodle', null, 'en'), $sm->get_string('yes', 'moodle', null, 'en')], $options);
        $this->assertSame(get_string('no'), $options[0]);
    }

    /**
     * Audit L4: core never translates a stored role name, so a (re)created lock role is named in
     * the site default language - not in the language of the student whose start triggered it.
     */
    public function test_role_is_named_in_site_language(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->register_german();
        $CFG->lang = 'de';
        $this->setUser($this->getDataGenerator()->create_user(['lang' => 'en']));
        delete_role(lock_manager::get_roleid());

        $role = $DB->get_record('role', ['id' => lock_manager::get_roleid()]);
        $this->assertSame($this->strings('de')['rolename'], $role->name);
        $this->assertSame($this->strings('de')['roledescription'], $role->description);
    }

    /**
     * Stored settings do not depend on the language they were saved or are read in.
     */
    public function test_saved_setting_is_language_independent(): void {
        $this->resetAfterTest();
        $this->register_german();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($this->getDataGenerator()->create_user(['lang' => 'de']));
        $quiz = $this->getDataGenerator()->get_plugin_generator('mod_quiz')
            ->create_instance(['course' => $course->id, 'messaginglockenabled' => 1]);

        foreach (['de', 'en'] as $lang) {
            force_current_language($lang);
            $this->assertEquals(1, \mod_quiz\access_manager::load_settings($quiz->id)['messaginglockenabled'], $lang);
            $this->assertTrue(lock_manager::is_enabled($quiz->id), $lang);
        }
        force_current_language('');
    }
}
