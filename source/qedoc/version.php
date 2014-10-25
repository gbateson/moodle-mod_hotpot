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

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

if (floatval($GLOBALS['CFG']->release) <= 2.6) {
    $plugin = new stdClass();
}

$plugin->cron      = 0;
$plugin->component = 'hotpotsource_qedoc';
$plugin->maturity  = MATURITY_STABLE;
$plugin->requires  = 2010112400;
$plugin->version   = 2014091931;
$plugin->dependencies = array('mod_hotpot' => 2014091931);

if (floatval($GLOBALS['CFG']->release) <= 2.6) {
    $module = clone($plugin);
}
