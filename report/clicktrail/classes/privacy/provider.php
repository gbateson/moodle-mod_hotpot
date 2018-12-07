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
 * Privacy Subsystem implementation for hotpotreport_clicktrail.
 *
 * @package    hotpotreport_clicktrail
 * @category   privacy
 * @copyright  2018 onwards Catalyst IT Ltd <http://catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Anupama Dharmajan <anupamadharmajan@catalyst-au.net>
 */

namespace hotpotreport_clicktrail\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * The hotpotreport_clicktrail module does not store any data.
 *
 * @package    hotpotreport_clicktrail
 * @copyright  2018 onwards Catalyst IT Ltd <http://catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Anupama Dharmajan <anupamadharmajan@catalyst-au.net>
 */
class provider implements \core_privacy\local\metadata\null_provider {

    // This polyfill allows the provider to work on both old (pre-7)
    // and new PHP versions. Thanks to Dan Marsden for this suggestion.
    use \core_privacy\local\legacy_polyfill;

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function _get_reason() {
        return 'privacy:metadata';
    }
}
