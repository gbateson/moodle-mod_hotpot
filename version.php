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

// the following two lines are required for
// validation checks on plugins at moodle.org
/*****************************
$plugin->release = '99.99.99';
$plugin->version = 9999999999;
*****************************/

if (isset($plugin) && is_object($plugin)) {
    // Moodle >= 2.5
    $object = 'plugin';
} else {
    // Moodle <= 2.4
    $object = 'module';
}

$$object->cron      = 0;
$$object->component = 'mod_hotpot';
$$object->maturity  = MATURITY_STABLE; // ALPHA=50, BETA=100, RC=150, STABLE=200
$$object->requires  = 2010112400; // Moodle 2.0
$$object->release   = '2014.04.27 (13)';
$$object->version   = 2014042713;

unset($object);
