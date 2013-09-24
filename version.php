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
 * Defines the version of hotpot
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package   mod-hotpot
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$module->cron      = 0;
$module->component = 'mod_hotpot';
$module->maturity  = MATURITY_STABLE; // = 200
$module->release   = 'v3.0.71';
$module->requires  = 2010112400; // 2011070100; // Moodle 2.1
$module->version   = 2010080371;

// actually this version of the HotPot module can run on
// *any* version of Moodle 2.x, starting from Moodle 2.0
$name = 'requ'.'ires';
$value = 2010112400; // Moodle 2.0
if (isset($CFG->version) && $CFG->version < $module->$name && $CFG->version > $value) {
    $module->$name = $value;
}
