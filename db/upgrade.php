<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade script for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Performs PassionFinder database upgrades.
 *
 * This function is required by Moodle plugin validation even when the plugin
 * has no upgrade steps yet. Future schema changes can be added here using
 * old-version checks and upgrade_mod_savepoint().
 *
 * @param int $oldversion The version currently installed.
 * @return bool True on success.
 */
function xmldb_passionfinder_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    // Version 2026090700 changes only the choice-set generation algorithm.
    // No database schema change is required.
    if ($oldversion < 2026090700) {
        upgrade_mod_savepoint(true, 2026090700, 'passionfinder');
    }

    // Version 2026090701 restores the Moodle-native JSON filepicker UI.
    // No database schema change is required.
    if ($oldversion < 2026090701) {
        upgrade_mod_savepoint(true, 2026090701, 'passionfinder');
    }

    return true;
}
