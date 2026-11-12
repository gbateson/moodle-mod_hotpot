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
 * Render an attempt at a HotPot quiz
 * Output format: hp_7_jmatch_xml_flashcard
 *
 * @package   hotpotattempt_hp
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Get parent class.
require_once($CFG->dirroot . '/mod/hotpot/attempt/hp/7/jmatch/xml/renderer.php');

/**
 * mod_hotpot_attempt_hp_7_jmatch_xml_flashcard_renderer
 *
 * @copyright 2026 Gordon Bateson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class mod_hotpot_attempt_hp_7_jmatch_xml_flashcard_renderer extends mod_hotpot_attempt_hp_7_jmatch_xml_renderer {
    /** @var string */
    public $templatefile = 'fjmatch7.ht_';
    /** @var string */
    public $js_object_type = 'JMatchFlashcard';

    /**
     * init
     *
     * @param xxx $hotpot
     */
    public function init($hotpot) {
        parent::init($hotpot);

        // Replace standard jmatch.js with flashcard.js.
        $this->javascripts = preg_grep('/jmatch.js/', $this->javascripts, PREG_GREP_INVERT);
        array_push($this->javascripts, 'mod/hotpot/attempt/hp/6/jmatch/flashcard.js');
    }

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    public static function sourcetypes() {
        return array('hp_6_jmatch_xml', 'hp_7_jmatch_xml');
    }

    /**
     * fix_js_StartUp_DragAndDrop
     *
     * @param xxx $substr (passed by reference)
     */
    public function fix_js_StartUp_DragAndDrop(&$substr) {
        $this->fix_js_StartUp_DragAndDrop_Flashcard($substr);
    }

    /**
     * fix_mediafilter_onload_extra
     *
     * @return xxx
     */
    public function fix_mediafilter_onload_extra() {
        // Automatically show first item.
        return $this->fix_mediafilter_onload_extra_Flashcard();
    }

    /**
     * get_js_functionnames
     *
     * @return xxx
     */
    public function get_js_functionnames() {
        // Start list of function names.
        $names = parent::get_js_functionnames();
        $names .= ($names ? ',' : '') . 'DeleteItem,ShowItem';
        return $names;
    }

    /**
     * fix_js_DeleteItem
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     */
    public function fix_js_DeleteItem(&$str, $start, $length) {
        $this->fix_js_DeleteItem_Flashcard($str, $start, $length);
    }

    /**
     * fix_js_ShowItem
     *
     * @param xxx $str (passed by reference)
     * @param xxx $start
     * @param xxx $length
     */
    public function fix_js_ShowItem(&$str, $start, $length) {
        $this->fix_js_ShowItem_Flashcard($str, $start, $length);
    }

    /**
     * get_stop_function_name
     *
     * @return xxx
     */
    public function get_stop_function_name() {
        return 'HP_send_results';
    }

    /**
     * get_stop_function_args
     *
     * @return xxx
     */
    public function get_stop_function_args() {
        return $this->get_send_results_event();
    }
}
