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
 * Library functions for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns whether the plugin supports a Moodle feature.
 *
 * @param string $feature Feature constant.
 * @return bool|null True, false, or null when unknown.
 */
function passionfinder_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}

/**
 * Adds a PassionFinder instance.
 *
 * @param stdClass $data Form data.
 * @param mod_passionfinder_mod_form|null $mform The form instance.
 * @return int The new instance id.
 */
function passionfinder_add_instance(stdClass $data, ?mod_passionfinder_mod_form $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    if (!isset($data->instrumentjson)) {
        $data->instrumentjson = '';
    }

    return $DB->insert_record('passionfinder', $data);
}

/**
 * Updates a PassionFinder instance.
 *
 * @param stdClass $data Form data.
 * @param mod_passionfinder_mod_form|null $mform The form instance.
 * @return bool
 */
function passionfinder_update_instance(stdClass $data, ?mod_passionfinder_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    if (!isset($data->instrumentjson)) {
        $data->instrumentjson = '';
    }

    return $DB->update_record('passionfinder', $data);
}

/**
 * Deletes a PassionFinder instance and related records.
 *
 * @param int $id Activity instance id.
 * @return bool
 */
function passionfinder_delete_instance(int $id): bool {
    global $DB;

    if (!$DB->record_exists('passionfinder', ['id' => $id])) {
        return false;
    }

    $attemptids = $DB->get_fieldset_select('passionfinder_attempts', 'id', 'passionfinderid = ?', [$id]);

    if (!empty($attemptids)) {
        [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('passionfinder_results', 'attemptid ' . $insql, $params);
        $DB->delete_records_select('passionfinder_choices', 'attemptid ' . $insql, $params);
        $DB->delete_records_select('passionfinder_sets', 'attemptid ' . $insql, $params);
    }

    $DB->delete_records('passionfinder_attempts', ['passionfinderid' => $id]);
    $DB->delete_records('passionfinder', ['id' => $id]);

    return true;
}
