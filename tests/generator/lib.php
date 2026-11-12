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
 * Data generator for mod_hotpot (used by PHPUnit and Behat tests).
 *
 * @package   mod_hotpot
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_hotpot data generator
 *
 * Create a HotPot activity from a source file, for example:
 *     $generator->create_instance([
 *         'course' => $course->id,
 *         'sourcefile' => 'mod/hotpot/tests/fixtures/hp7_jcloze.jcl',
 *         'outputformat' => 'hp_7_jcloze_xml_v7',
 *     ]);
 *
 * @package   mod_hotpot
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_hotpot_generator extends testing_module_generator {

    /** @var string default source file, relative to $CFG->dirroot */
    const DEFAULT_SOURCEFILE = 'mod/hotpot/tests/fixtures/hp7_jquiz.jqz';

    /**
     * Create a new instance of the HotPot activity.
     *
     * The "sourcefile" field is the path, relative to $CFG->dirroot, of a Hot Potatoes file.
     * The file is added to the activity, and its type is detected automatically.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass record from the hotpot table, with the extra property "cmid"
     */
    public function create_instance($record = null, $options = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/hotpot/locallib.php');

        $record = (object)(array)$record;

        $sourcefile = (empty($record->sourcefile) ? self::DEFAULT_SOURCEFILE : $record->sourcefile);
        unset($record->sourcefile);

        $defaults = [
            'sourceitemid' => 0,
            'sourcelocation' => hotpot::LOCATION_COURSEFILES,
            'configfile' => '',
            'outputformat' => '',
            'navigation' => hotpot::NAVIGATION_MOODLE,
            'usemediafilter' => '',
            'studentfeedback' => hotpot::FEEDBACK_NONE,
            'studentfeedbackurl' => '',
            'password' => '',
            'subnet' => '',
            'timelimit' => hotpot::TIME_SPECIFIC,
            'timelimitspecific' => 0,
            // Send results immediately when a quiz is finished.
            'delay3' => hotpot::TIME_SPECIFIC,
            'delay3specific' => 0,
            'grademethod' => hotpot::GRADEMETHOD_HIGHEST,
            'gradeweighting' => 100,
            'reviewoptions' => 0,
        ];
        foreach ($defaults as $name => $value) {
            if (! isset($record->$name)) {
                $record->$name = $value;
            }
        }

        $instance = parent::create_instance($record, (array)$options);

        // Add the source file to the activity, and detect its type.
        $context = context_module::instance($instance->cmid);
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'mod_hotpot',
            'filearea' => 'sourcefile',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => basename($sourcefile),
            'sortorder' => 1,
        ];
        $file = get_file_storage()->create_file_from_pathname($filerecord, $CFG->dirroot . '/' . $sourcefile);

        $instance->sourcefile = '/' . $file->get_filename();
        $instance->sourcetype = hotpot::get_sourcetype($file);
        $DB->update_record('hotpot', (object)[
            'id' => $instance->id,
            'sourcefile' => $instance->sourcefile,
            'sourcetype' => $instance->sourcetype,
        ]);

        return $instance;
    }
}
