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
 * Activity settings form for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Defines the PassionFinder module settings form.
 *
 * @package    mod_passionfinder
 */
class mod_passionfinder_mod_form extends moodleform_mod {
    /**
     * Defines form elements.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'mod_passionfinder'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('intro', 'mod_passionfinder'));

        $mform->addElement('header', 'activitysettings', get_string('activitysettings', 'mod_passionfinder'));

        $mform->addElement('textarea', 'instrumentjson', get_string('instrumentjson', 'mod_passionfinder'),
            ['rows' => 18, 'cols' => 90]);
        $mform->setType('instrumentjson', PARAM_RAW);
        $mform->addHelpButton('instrumentjson', 'instrumentjson', 'mod_passionfinder');

        $mform->addElement('select', 'itemsperround', get_string('itemsperround', 'mod_passionfinder'),
            [3 => 3, 4 => 4, 5 => 5, 6 => 6]);
        $mform->setDefault('itemsperround', 5);
        $mform->addHelpButton('itemsperround', 'itemsperround', 'mod_passionfinder');

        $roundoptions = [
            4 => 4,
            6 => 6,
            8 => 8,
            10 => 10,
            12 => 12,
            16 => 16,
        ];
        $mform->addElement('select', 'roundspercategory', get_string('roundspercategory', 'mod_passionfinder'),
            $roundoptions);
        $mform->setDefault('roundspercategory', 8);
        $mform->addHelpButton('roundspercategory', 'roundspercategory', 'mod_passionfinder');

        $resultoptions = [
            3 => 3,
            5 => 5,
            10 => 10,
        ];
        $mform->addElement('select', 'resultdepth', get_string('resultdepth', 'mod_passionfinder'),
            $resultoptions);
        $mform->setDefault('resultdepth', 5);
        $mform->addHelpButton('resultdepth', 'resultdepth', 'mod_passionfinder');

        $mform->addElement('text', 'mostlabel', get_string('mostlabel', 'mod_passionfinder'), ['size' => '40']);
        $mform->setType('mostlabel', PARAM_TEXT);
        $mform->setDefault('mostlabel', get_string('defaultmostlabel', 'mod_passionfinder'));
        $mform->addHelpButton('mostlabel', 'mostlabel', 'mod_passionfinder');

        $mform->addElement('text', 'leastlabel', get_string('leastlabel', 'mod_passionfinder'), ['size' => '40']);
        $mform->setType('leastlabel', PARAM_TEXT);
        $mform->setDefault('leastlabel', get_string('defaultleastlabel', 'mod_passionfinder'));
        $mform->addHelpButton('leastlabel', 'leastlabel', 'mod_passionfinder');

        $mform->addElement('header', 'instrumentbuilderheader', get_string('instrumentbuilder', 'mod_passionfinder'));

        if (!empty($this->current->coursemodule)) {
            $builderurl = new moodle_url('/mod/passionfinder/builder.php', ['id' => $this->current->coursemodule]);
            $builderhtml = html_writer::tag('p', get_string('buildersettingsintro', 'mod_passionfinder')) .
                html_writer::tag('p', get_string('buildersettingswarning', 'mod_passionfinder'), ['class' => 'alert alert-warning']) .
                html_writer::link($builderurl, get_string('openinstrumentbuilder', 'mod_passionfinder'), ['class' => 'btn btn-primary']);

            $mform->addElement('static', 'builderlink', get_string('builderlinklabel', 'mod_passionfinder'), $builderhtml);
        } else {
            $mform->addElement('static', 'builderlinkpending', get_string('builderlinklabel', 'mod_passionfinder'),
                get_string('builderlinkpending', 'mod_passionfinder'));
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Validates form data.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!empty(trim($data['instrumentjson'] ?? ''))) {
            json_decode($data['instrumentjson']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['instrumentjson'] = get_string('jsoninvalid', 'mod_passionfinder');
            }
        }

        return $errors;
    }
}
