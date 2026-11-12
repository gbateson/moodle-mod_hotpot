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
 * mod/hotpot/classes/completion/custom_completion.php
 *
 * This class is only used by Moodle >= 3.11.
 * Earlier versions of Moodle use hotpot_get_completion_state() in "lib.php".
 *
 * @package    mod_hotpot
 * @copyright  2026 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.11
 */

namespace mod_hotpot\completion;

defined('MOODLE_INTERNAL') || die();

/**
 * Activity custom completion subclass for the HotPot activity.
 *
 * The custom rules are defined in hotpot_get_coursemodule_info() in "lib.php".
 */
class custom_completion extends \core_completion\activity_custom_completion {
    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $CFG, $DB;

        $this->validate_rule($rule);

        switch ($rule) {
            case 'completionmingrade':
                $mingrade = floatval($this->cm->customdata['customcompletionrules']['completionmingrade']);
                $grade = $this->get_user_grade();
                $state = ($grade && $grade->finalgrade !== null && $grade->finalgrade >= $mingrade);
                break;

            case 'completionpass':
                $grade = $this->get_user_grade();
                $state = ($grade && $grade->is_passed());
                break;

            case 'completioncompleted':
                require_once($CFG->dirroot . '/mod/hotpot/locallib.php');
                $params = array('hotpotid' => $this->cm->instance,
                                'userid'   => $this->userid,
                                'status'   => \hotpot::STATUS_COMPLETED);
                $state = $DB->record_exists('hotpot_attempts', $params);
                break;

            default:
                $state = false;
        }

        return ($state ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE);
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return array('completionmingrade', 'completionpass', 'completioncompleted');
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $plugin = 'mod_hotpot';
        $mingrade = 0;
        if (isset($this->cm->customdata['customcompletionrules']['completionmingrade'])) {
            $mingrade = $this->cm->customdata['customcompletionrules']['completionmingrade'];
        }
        return array(
            'completionmingrade' => get_string('completiondetail:mingrade', $plugin, format_float($mingrade, -1)),
            'completionpass' => get_string('completiondetail:pass', $plugin),
            'completioncompleted' => get_string('completiondetail:completed', $plugin),
        );
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     * This must include all the standard rules that a HotPot activity can use.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return array(
            'completionview',
            'completionusegrade',
            'completionpassgrade',
            'completionmingrade',
            'completionpass',
            'completioncompleted',
        );
    }

    /**
     * Get the current user's grade for this HotPot, if any.
     *
     * @return \grade_grade|false
     */
    protected function get_user_grade() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $params = array('courseid'     => $this->cm->course,
                        'itemtype'     => 'mod',
                        'itemmodule'   => 'hotpot',
                        'iteminstance' => $this->cm->instance,
                        'itemnumber'   => 0);
        if ($gradeitem = \grade_item::fetch($params)) {
            $grades = \grade_grade::fetch_users_grades($gradeitem, array($this->userid), false);
            if (isset($grades[$this->userid])) {
                return $grades[$this->userid];
            }
        }
        return false;
    }
}
