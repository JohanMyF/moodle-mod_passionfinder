<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * JSON import form for PassionFinder.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_passionfinder\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Moodle-native JSON import form.
 */
class import_form extends \moodleform {
    /**
     * Defines the form.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'importjsonfile', get_string('jsonfile', 'mod_passionfinder'), null, [
            'accepted_types' => ['.json'],
            'maxbytes' => 524288,
        ]);
        $mform->addElement('submit', 'importfilebutton', get_string('validateimportjsonfile', 'mod_passionfinder'));

        $mform->addElement('static', 'advancedjsonintro', '', get_string('advancedjsonintro', 'mod_passionfinder'));
        $mform->addElement('textarea', 'importjsontext', get_string('pastejson', 'mod_passionfinder'), [
            'rows' => 10,
            'cols' => 90,
        ]);
        $mform->setType('importjsontext', PARAM_RAW);
        $mform->addElement('static', 'currentjsonhinttext', '', get_string('currentjsonhint', 'mod_passionfinder'));
        $mform->addElement('submit', 'importpastebutton', get_string('validateimportpastedjson', 'mod_passionfinder'));
    }
}
