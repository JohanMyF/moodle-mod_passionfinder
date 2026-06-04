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
 * Backup task for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the PassionFinder backup activity task.
 *
 * @package    mod_passionfinder
 */
class backup_passionfinder_activity_task extends backup_activity_task {
    /**
     * Defines module-specific backup settings.
     *
     * @return void
     */
    protected function define_my_settings(): void {
    }

    /**
     * Defines module-specific backup steps.
     *
     * @return void
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_passionfinder_activity_structure_step('passionfinder_structure', 'passionfinder.xml'));
    }

    /**
     * Encodes content links.
     *
     * @param string $content Content.
     * @return string Encoded content.
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot . '/mod/passionfinder', '#');

        $search = [
            "#({$base}/index\\.php\\?id=)([0-9]+)#",
            "#({$base}/view\\.php\\?id=)([0-9]+)#",
        ];

        $replace = [
            '$@PASSIONFINDERINDEX*$2@$',
            '$@PASSIONFINDERVIEWBYID*$2@$',
        ];

        return preg_replace($search, $replace, $content);
    }
}
