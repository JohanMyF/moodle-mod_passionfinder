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
 * Restore task for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the PassionFinder restore activity task.
 *
 * @package    mod_passionfinder
 */
class restore_passionfinder_activity_task extends restore_activity_task {
    /**
     * Defines module-specific restore settings.
     *
     * @return void
     */
    protected function define_my_settings(): void {
    }

    /**
     * Defines module-specific restore steps.
     *
     * @return void
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_passionfinder_activity_structure_step('passionfinder_structure', 'passionfinder.xml'));
    }

    /**
     * Defines content decoding rules.
     *
     * @return array
     */
    public static function define_decode_contents(): array {
        return [
            new restore_decode_content('passionfinder', ['intro'], 'passionfinder'),
        ];
    }

    /**
     * Defines link decoding rules.
     *
     * @return array
     */
    public static function define_decode_rules(): array {
        return [
            new restore_decode_rule('PASSIONFINDERVIEWBYID', '/mod/passionfinder/view.php?id=$1', 'course_module'),
            new restore_decode_rule('PASSIONFINDERINDEX', '/mod/passionfinder/index.php?id=$1', 'course'),
        ];
    }
}
