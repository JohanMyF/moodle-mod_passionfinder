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
 * Restore structure step for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restores the PassionFinder activity structure.
 *
 * @package    mod_passionfinder
 */
class restore_passionfinder_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the restore structure.
     *
     * @return array
     */
    protected function define_structure(): array {
        $paths = [];

        $paths[] = new restore_path_element('passionfinder', '/activity/passionfinder');

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('passionfinder_attempt', '/activity/passionfinder/attempts/attempt');
            $paths[] = new restore_path_element('passionfinder_set', '/activity/passionfinder/attempts/attempt/sets/set');
            $paths[] = new restore_path_element('passionfinder_choice', '/activity/passionfinder/attempts/attempt/choices/choice');
            $paths[] = new restore_path_element('passionfinder_result', '/activity/passionfinder/attempts/attempt/results/result');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Processes the main activity record.
     *
     * @param array|stdClass $data Data.
     * @return void
     */
    protected function process_passionfinder($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->timecreated = $data->timecreated ?? time();
        $data->timemodified = time();

        $newitemid = $DB->insert_record('passionfinder', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('passionfinder', $oldid, $newitemid);
    }

    /**
     * Processes an attempt record.
     *
     * @param array|stdClass $data Data.
     * @return void
     */
    protected function process_passionfinder_attempt($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->passionfinderid = $this->get_new_parentid('passionfinder');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);

        if (empty($data->userid)) {
            return;
        }

        $newitemid = $DB->insert_record('passionfinder_attempts', $data);
        $this->set_mapping('passionfinder_attempt', $oldid, $newitemid);
    }

    /**
     * Processes a frozen set record.
     *
     * @param array|stdClass $data Data.
     * @return void
     */
    protected function process_passionfinder_set($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->attemptid = $this->get_new_parentid('passionfinder_attempt');

        $newitemid = $DB->insert_record('passionfinder_sets', $data);
        $this->set_mapping('passionfinder_set', $oldid, $newitemid);
    }

    /**
     * Processes a choice record.
     *
     * @param array|stdClass $data Data.
     * @return void
     */
    protected function process_passionfinder_choice($data): void {
        global $DB;

        $data = (object) $data;

        $data->attemptid = $this->get_new_parentid('passionfinder_attempt');
        $data->setid = $this->get_mappingid('passionfinder_set', $data->setid, 0);

        if (empty($data->setid)) {
            return;
        }

        $DB->insert_record('passionfinder_choices', $data);
    }

    /**
     * Processes a result record.
     *
     * @param array|stdClass $data Data.
     * @return void
     */
    protected function process_passionfinder_result($data): void {
        global $DB;

        $data = (object) $data;

        $data->attemptid = $this->get_new_parentid('passionfinder_attempt');

        $DB->insert_record('passionfinder_results', $data);
    }

    /**
     * Executes after restore.
     *
     * @return void
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_passionfinder', 'intro', null);
    }
}
