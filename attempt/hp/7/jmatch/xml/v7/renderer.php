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
 * Output format: hp_7_jmatch_xml_v7
 *
 * @package   hotpotattempt_hp
 * @copyright 2020 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Get parent class.
require_once($CFG->dirroot . '/mod/hotpot/attempt/hp/7/jmatch/xml/renderer.php');

/**
 * mod_hotpot_attempt_hp_7_jmatch_xml_v7_renderer
 *
 * @copyright 2020 Gordon Bateson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class mod_hotpot_attempt_hp_7_jmatch_xml_v7_renderer extends mod_hotpot_attempt_hp_7_jmatch_xml_renderer {
    /** @var string HP7 templates use a JSON object (V7JsonEx) and generated HTML (V7SelectEx, V7Cards) */
    public $templatestrings = 'PreloadImageList|QsToShow|V7JsonEx|V7SelectEx|V7Cards';

    /** @var mixed left and right items in HP7 format (see get_v7_items) */
    protected $v7_items = null;

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    public static function sourcetypes() {
        return array('hp_6_jmatch_xml', 'hp_7_jmatch_xml');
    }

    /**
     * get_v7_items
     *
     * Get the left and right items in the format used by HP7:
     *  - Group: the position of the first pair that has the same left text,
     *           so that left items with the same text share a group
     *  - Groups: the groups of all pairs that have the same right text,
     *           so that right items with the same text are interchangeable
     *  - MatchedWith: the position of a fixed (i.e. pre-matched) right item, otherwise -1
     * Pairs with an empty left item are distractors, and only appear in the right items.
     *
     * @return stdClass with properties "left" and "right" (arrays of stdClass)
     */
    protected function get_v7_items() {
        if ($this->v7_items) {
            return $this->v7_items;
        }
        $source = $this->hotpot->source;

        $pairs = array();
        $tags = 'data,matching-exercise,pair';
        $i = 0;
        while (($item = "[$i]['#']") && $source->xml_value($tags, $item)) {
            $l_item = $item . "['left-item'][0]['#']";
            $r_item = $item . "['right-item'][0]['#']";
            $pairs[$i] = (object)array(
                'ltext'  => (string)$source->xml_value($tags, $l_item . "['text'][0]['#']"),
                'rtext'  => (string)$source->xml_value($tags, $r_item . "['text'][0]['#']"),
                'rfixed' => $source->xml_value_int($tags, $r_item . "['fixed'][0]['#']"),
            );
            $i++;
        }

        // Group of each pair = position of first pair with the same left text.
        $groups = array();
        foreach ($pairs as $i => $pair) {
            if (! array_key_exists($pair->ltext, $groups)) {
                $groups[$pair->ltext] = $i;
            }
            $pair->group = $groups[$pair->ltext];
        }

        // Groups for each right text.
        $rgroups = array();
        foreach ($pairs as $i => $pair) {
            if (! isset($rgroups[$pair->rtext])) {
                $rgroups[$pair->rtext] = array();
            }
            if (! in_array($pair->group, $rgroups[$pair->rtext])) {
                $rgroups[$pair->rtext][] = $pair->group;
            }
        }

        $items = (object)array('left' => array(), 'right' => array());
        foreach ($pairs as $i => $pair) {
            if ($pair->ltext !== '') {
                $items->left[] = (object)array(
                    'OrigPos' => $i,
                    'Group' => $pair->group,
                    'text' => $pair->ltext,
                );
            }
            if ($pair->rtext !== '') {
                $items->right[] = (object)array(
                    'OrigPos' => $i,
                    'Groups' => $rgroups[$pair->rtext],
                    'MatchedWith' => ($pair->rfixed && $pair->ltext !== '') ? $i : -1,
                    'text' => $pair->rtext,
                );
            }
        }
        return ($this->v7_items = $items);
    }

    /**
     * expand_V7JsonEx
     *
     * @return string javascript
     */
    public function expand_V7JsonEx() {
        $items = $this->get_v7_items();
        $config = $this->hotpot->source->hbs_software . '-config-file,' . $this->hotpot->source->hbs_quiztype;

        $itemstoshow = -1;
        if ($this->hotpot->source->xml_value_int($config . ',show-limited-questions')) {
            $itemstoshow = $this->hotpot->source->xml_value_int($config . ',questions-to-show');
        }

        $json = array(
            'ShuffleLeftItems' => (bool)$this->hotpot->source->xml_value_int($config . ',shuffle-questions'),
            'IsSimple' => false,
            'ItemsToShow' => $itemstoshow,
            'LeftItems' => array(),
            'RightItems' => array(),
        );
        foreach ($items->left as $item) {
            $json['LeftItems'][] = array('OrigPos' => $item->OrigPos, 'Group' => $item->Group);
        }
        foreach ($items->right as $item) {
            $json['RightItems'][] = array('OrigPos' => $item->OrigPos, 'Groups' => $item->Groups,
                                          'MatchedWith' => $item->MatchedWith);
        }
        $json = json_encode($json);
        $json = str_replace(array('\\', "'"), array('\\\\', "\\'"), $json);
        return "var V7JsonEx = '$json';";
    }

    /**
     * expand_V7SelectEx
     *
     * @return string HTML table of left items and select elements
     */
    public function expand_V7SelectEx() {
        $items = $this->get_v7_items();

        $default = $this->hotpot->source->xml_value('data,matching-exercise,default-right-item');
        $options = '<option value="-1">' . $default . '</option>' . "\n";

        $keys = array_keys($items->right);
        $this->seed_random_number_generator();
        shuffle($keys);
        foreach ($keys as $key) {
            $item = $items->right[$key];
            $options .= '<option value="' . $item->OrigPos . '" data-origPos="R_' . $item->OrigPos . '">' . $item->text . '</option>' . "\n";
        }

        $str = '<table class="MatchTable">' . "\n" . '<tbody id="MatchItems">' . "\n";
        foreach ($items->left as $item) {
            $pos = $item->OrigPos;
            $str .= '<tr><td id="L_' . $pos . '" class="LeftItem">' . $item->text . '</td>' . "\n"
                   . '<td class="RightItem">' . "\n"
                   . '<select onchange="JsonEx.AlignSelects(' . $pos . ')" id="R_' . $pos . '">' . "\n"
                   . $options
                   . '</select>' . "\n"
                   . '</td>' . "\n"
                   . '<td> </td>' . "\n"
                   . '</tr>' . "\n";
        }
        $str .= '</tbody>' . "\n" . '</table>';
        return $str;
    }

    /**
     * expand_V7Cards
     *
     * @return string HTML for the drag-and-drop cards
     */
    public function expand_V7Cards() {
        $items = $this->get_v7_items();
        $str = '';
        foreach ($items->left as $item) {
            $str .= '<div id="L_' . $item->OrigPos . '" class="CardStyle">' . $item->text . '</div>' . "\n";
        }
        foreach ($items->right as $item) {
            $str .= '<div id="R_' . $item->OrigPos . '" class="CardStyle">' . $item->text . '</div>' . "\n";
        }
        return $str;
    }
}
