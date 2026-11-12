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
require_once($CFG->dirroot . '/mod/hotpot/locallib.php');

/**
 * Tests for rendering the attempt page of HotPot activities in various output formats.
 *
 * @package   mod_hotpot
 * @category  test
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_test extends \advanced_testcase {

    /**
     * Render the attempt page for a HotPot activity, as a student would see it.
     *
     * @param string $sourcefile name of a file in the "tests/fixtures" folder
     * @param string $outputformat
     * @return string HTML of the attempt page
     */
    protected function render_attempt($sourcefile, $outputformat) {
        global $CFG, $DB, $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $hotpot = $this->getDataGenerator()->create_module('hotpot', [
            'course' => $course->id,
            'sourcefile' => 'mod/hotpot/tests/fixtures/' . $sourcefile,
            'outputformat' => $outputformat,
        ]);
        $this->setUser($student);

        $cm = get_coursemodule_from_id('hotpot', $hotpot->cmid, 0, false, MUST_EXIST);
        $record = $DB->get_record('hotpot', ['id' => $hotpot->id], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $PAGE->set_cm($cm, $course);
        $hotpot = \hotpot::create($record, $cm, $course, $context);
        $PAGE->set_url($hotpot->attempt_url());

        $subtype = $hotpot->get_attempt_renderer_subtype();
        $this->assertSame('attempt_' . $outputformat, $subtype);
        require_once($CFG->dirroot . '/mod/hotpot/' . str_replace('_', '/', $subtype) . '/renderer.php');
        $output = $PAGE->get_renderer('mod_hotpot', $subtype);

        ob_start();
        $output->render_attempt($hotpot);
        ob_end_clean();
        $this->assertDebuggingNotCalled();

        // Note: when running tests, the page header has no "</head>" tag,
        // so we check the head and body content prepared by the renderer.
        $html = $output->headcontent . $output->bodycontent;
        $this->assertStringContainsString('HP_send_results', $html);
        return $html;
    }

    /**
     * Test the HP6 JQuiz output format.
     */
    public function test_hp6_jquiz(): void {
        $html = $this->render_attempt('hp6_jquiz.jqz', 'hp_6_jquiz_xml_v6');
        $this->assertStringContainsString('new JQuiz(', $html);
    }

    /**
     * Test the HP7 JQuiz output format.
     */
    public function test_hp7_jquiz(): void {
        $html = $this->render_attempt('hp7_jquiz.jqz', 'hp_7_jquiz_xml_v7');
        $this->assertStringContainsString('new JQuiz(', $html);
        $this->assertStringContainsString('ExerciseContainer', $html);
    }

    /**
     * Test the HP7 JCloze output format.
     */
    public function test_hp7_jcloze(): void {
        $html = $this->render_attempt('hp7_jcloze.jcl', 'hp_7_jcloze_xml_v7');
        $this->assertStringContainsString('new JCloze(', $html);
        $this->assertStringContainsString('id="Gap0"', $html);
    }

    /**
     * Test the HP7 JMatch output format (select elements).
     */
    public function test_hp7_jmatch(): void {
        $html = $this->render_attempt('hp7_jmatch.jmt', 'hp_7_jmatch_xml_v7');
        $this->assertStringContainsString('new JMatch(', $html);
        $this->assertStringContainsString('var V7JsonEx = ', $html);
        $this->assertStringContainsString('<select onchange="JsonEx.AlignSelects(0)" id="R_0">', $html);
    }

    /**
     * Test the HP7 JMatch drag-and-drop output format.
     */
    public function test_hp7_jmatch_drag(): void {
        $html = $this->render_attempt('hp7_jmatch.jmt', 'hp_7_jmatch_xml_v7_plus');
        $this->assertStringContainsString('var V7JsonEx = ', $html);
        $this->assertStringContainsString('DragEx.Setup(); StartUp()', $html);
        $this->assertStringContainsString('<div id="R_0" class="CardStyle">', $html);
    }

    /**
     * Test the output format for HP7 JMatch html files.
     */
    public function test_hp7_jmatch_html(): void {
        $html = $this->render_attempt('hp7_jmatch.htm', 'hp_7_jmatch_html');
        $this->assertStringContainsString('JsonEx.Setup(); StartUp()', $html);
    }
}
