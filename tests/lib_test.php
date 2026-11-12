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

namespace mod_hotpot;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/hotpot/lib.php');
require_once($CFG->dirroot . '/mod/hotpot/locallib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Basic tests for mod_hotpot: features, file types, output formats, activities and completion.
 *
 * @package   mod_hotpot
 * @category  test
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * Create a stored file from a file in the "tests/fixtures" folder.
     *
     * @param string $filename
     * @return \stored_file
     */
    protected function create_fixture_file($filename) {
        global $CFG;
        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'mod_hotpot',
            'filearea' => 'unittest',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $path = $CFG->dirroot . '/mod/hotpot/tests/fixtures/' . $filename;
        return get_file_storage()->create_file_from_pathname($filerecord, $path);
    }

    /**
     * Test the features supported by HotPot.
     */
    public function test_supports(): void {
        $this->assertTrue(hotpot_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertTrue(hotpot_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertTrue(hotpot_supports(FEATURE_COMPLETION_HAS_RULES));
        // On Moodle >= 2.7, "groupmembersonly" is replaced by conditional access,
        // and declaring it causes the plugin to be reported as broken on Moodle >= 5.0.
        $this->assertNotTrue(hotpot_supports('groupmembersonly'));
    }

    /**
     * Test the detection of the type of source files.
     */
    public function test_get_sourcetype(): void {
        $this->resetAfterTest();
        $expected = [
            'hp6_jquiz.jqz' => 'hp_6_jquiz_xml',
            'hp7_jquiz.jqz' => 'hp_7_jquiz_xml',
            'hp7_jcloze.jcl' => 'hp_7_jcloze_xml',
            'hp7_jmatch.jmt' => 'hp_7_jmatch_xml',
            'hp7_jmatch.htm' => 'hp_7_jmatch_html',
            // Masher files are not recognized.
            'masher.jms' => '',
        ];
        foreach ($expected as $filename => $sourcetype) {
            $file = $this->create_fixture_file($filename);
            $this->assertSame($sourcetype, \hotpot::get_sourcetype($file), $filename);
        }
    }

    /**
     * Test the list of output formats that are available for a source type.
     */
    public function test_available_outputformats_list(): void {
        $formats = \hotpot::available_outputformats_list('hp_7_jmatch_xml');
        foreach (['hp_7_jmatch_xml_v7', 'hp_7_jmatch_xml_v7_plus', 'hp_7_jmatch_xml_flashcard',
                  'hp_6_jmatch_xml_v6', 'hp_6_jmatch_xml_v6_plus'] as $format) {
            $this->assertArrayHasKey($format, $formats);
        }
        // Formats for other types of quiz are not included.
        $this->assertArrayNotHasKey('hp_7_jquiz_xml_v7', $formats);

        $formats = \hotpot::available_outputformats_list('hp_6_sequitur_xml');
        $this->assertArrayHasKey('hp_6_sequitur_xml', $formats);
        $this->assertArrayHasKey('hp_6_sequitur_xml_incremental', $formats);
    }

    /**
     * Test the creation of a HotPot activity, with a source file and a grade item.
     */
    public function test_create_instance(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $hotpot = $this->getDataGenerator()->create_module('hotpot', [
            'course' => $course->id,
            'name' => 'Test HotPot',
            'sourcefile' => 'mod/hotpot/tests/fixtures/hp7_jcloze.jcl',
        ]);

        $record = $DB->get_record('hotpot', ['id' => $hotpot->id], '*', MUST_EXIST);
        $this->assertSame('Test HotPot', $record->name);
        $this->assertSame('/hp7_jcloze.jcl', $record->sourcefile);
        $this->assertSame('hp_7_jcloze_xml', $record->sourcetype);

        $gradeitem = \grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod',
            'itemmodule' => 'hotpot', 'iteminstance' => $hotpot->id]);
        $this->assertNotEmpty($gradeitem);
        $this->assertEquals(100, $gradeitem->grademax);
    }

    /**
     * Test the custom completion rule for a minimum grade.
     */
    public function test_completion_mingrade(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $hotpot = $this->getDataGenerator()->create_module('hotpot', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionmingrade' => 50,
        ]);
        $cm = get_fast_modinfo($course)->get_cm($hotpot->cmid);
        $completion = new completion\custom_completion($cm, $student->id);

        $this->assertContains('completionmingrade', $completion->get_available_custom_rules());
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionmingrade'));

        $grade = ['userid' => $student->id, 'rawgrade' => 40];
        grade_update('mod/hotpot', $course->id, 'mod', 'hotpot', $hotpot->id, 0, $grade);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionmingrade'));

        $grade = ['userid' => $student->id, 'rawgrade' => 60];
        grade_update('mod/hotpot', $course->id, 'mod', 'hotpot', $hotpot->id, 0, $grade);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completionmingrade'));
    }
}
