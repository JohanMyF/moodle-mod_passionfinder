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
 * Backup structure step for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the PassionFinder backup structure.
 *
 * @package    mod_passionfinder
 */
class backup_passionfinder_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        $passionfinder = new backup_nested_element('passionfinder', ['id'], [
            'course',
            'name',
            'intro',
            'introformat',
            'instrumentjson',
            'itemsperround',
            'roundspercategory',
            'resultdepth',
            'mostlabel',
            'leastlabel',
            'timecreated',
            'timemodified',
        ]);

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid',
            'status',
            'currentset',
            'timecreated',
            'timemodified',
            'completedtime',
        ]);

        $sets = new backup_nested_element('sets');
        $set = new backup_nested_element('set', ['id'], [
            'categoryid',
            'setnumber',
            'itemsjson',
            'timecreated',
        ]);

        $choices = new backup_nested_element('choices');
        $choice = new backup_nested_element('choice', ['id'], [
            'setid',
            'categoryid',
            'mostitemid',
            'leastitemid',
            'timecreated',
            'timemodified',
        ]);

        $results = new backup_nested_element('results');
        $result = new backup_nested_element('result', ['id'], [
            'categoryid',
            'itemid',
            'appearances',
            'mostcount',
            'leastcount',
            'netscore',
            'normalisedscore',
            'rank',
            'timecreated',
        ]);

        $passionfinder->add_child($attempts);
        $attempts->add_child($attempt);
        $attempt->add_child($sets);
        $sets->add_child($set);
        $attempt->add_child($choices);
        $choices->add_child($choice);
        $attempt->add_child($results);
        $results->add_child($result);

        $passionfinder->set_source_table('passionfinder', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $attempt->set_source_table('passionfinder_attempts', ['passionfinderid' => backup::VAR_PARENTID]);
            $set->set_source_table('passionfinder_sets', ['attemptid' => backup::VAR_PARENTID]);
            $choice->set_source_table('passionfinder_choices', ['attemptid' => backup::VAR_PARENTID]);
            $result->set_source_table('passionfinder_results', ['attemptid' => backup::VAR_PARENTID]);

            $attempt->annotate_ids('user', 'userid');
        }

        $passionfinder->annotate_files('mod_passionfinder', 'intro', null);

        return $this->prepare_activity_structure($passionfinder);
    }
}
