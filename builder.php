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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Builder page for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_passionfinder\local\instrument_parser;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('passionfinder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/passionfinder:manage', $context);

$PAGE->set_url('/mod/passionfinder/builder.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($passionfinder->name) . ': ' . get_string('instrumentbuilder', 'mod_passionfinder'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$messages = [];
$opencategoryid = optional_param('opencategory', '', PARAM_TEXT);
$edititemid = optional_param('edititem', '', PARAM_TEXT);
$formurl = new moodle_url('/mod/passionfinder/builder.php', ['id' => $cm->id]);

if (optional_param('exportjson', 0, PARAM_BOOL)) {
    require_sesskey();

    $filename = clean_filename(format_string($passionfinder->name) . '-passionfinder.json');
    $json = trim((string) $passionfinder->instrumentjson);

    if ($json === '') {
        $config = passionfinder_builder_normalise_config(new stdClass(), $passionfinder);
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    send_file($json, $filename, 0, 0, true, true, 'application/json');
}

$config = passionfinder_builder_get_config($passionfinder);
$config = passionfinder_builder_normalise_config($config, $passionfinder);

if (optional_param('importjson', 0, PARAM_BOOL)) {
    require_sesskey();

    try {
        $importjson = trim(optional_param('importjsontext', '', PARAM_NOTAGS));
        $uploadedjson = passionfinder_builder_get_uploaded_json('importjsonfile');

        if ($uploadedjson !== null) {
            $importjson = $uploadedjson;
        }

        if ($importjson === '') {
            $messages[] = ['message' => get_string('builderimportempty', 'mod_passionfinder'), 'type' => 'error'];
        } else {
            $parsed = instrument_parser::parse($importjson, $passionfinder);
            passionfinder_builder_save_config($passionfinder->id, $parsed);
            $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
            $config = passionfinder_builder_get_config($passionfinder);
            $config = passionfinder_builder_normalise_config($config, $passionfinder);
            $messages[] = ['message' => get_string('builderimported', 'mod_passionfinder'), 'type' => 'success'];
        }
    } catch (moodle_exception $exception) {
        $messages[] = ['message' => get_string('builderimportinvalid', 'mod_passionfinder', $exception->getMessage()), 'type' => 'error'];
    }
}

if (optional_param('saveinstrumentmeta', 0, PARAM_BOOL)) {
    require_sesskey();

    $config->metadata->title = clean_param(required_param('instrumenttitle', PARAM_TEXT), PARAM_TEXT);
    $config->metadata->description = clean_param(optional_param('instrumentdescription', '', PARAM_TEXT), PARAM_TEXT);
    $config->settings->itemsperround = optional_param('itemsperround', $passionfinder->itemsperround, PARAM_INT);
    $config->settings->roundspercategory = optional_param('roundspercategory', $passionfinder->roundspercategory, PARAM_INT);
    $config->settings->resultdepth = optional_param('resultdepth', $passionfinder->resultdepth, PARAM_INT);
    $config->settings->mostlabel = clean_param(optional_param('mostlabel', $passionfinder->mostlabel, PARAM_TEXT), PARAM_TEXT);
    $config->settings->leastlabel = clean_param(optional_param('leastlabel', $passionfinder->leastlabel, PARAM_TEXT), PARAM_TEXT);
    $config->settings->showpositive = optional_param('showpositive', 0, PARAM_BOOL) ? true : false;
    $config->settings->shownegative = optional_param('shownegative', 0, PARAM_BOOL) ? true : false;
    $config->settings->positivevisual = passionfinder_builder_clean_visual(optional_param('positivevisual', 'hearts', PARAM_ALPHA));
    $config->settings->negativevisual = passionfinder_builder_clean_visual(optional_param('negativevisual', 'bubbles', PARAM_ALPHA));

    passionfinder_builder_save_config($passionfinder->id, $config);
    $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
    $messages[] = ['message' => get_string('buildersaved', 'mod_passionfinder'), 'type' => 'success'];
}

if (optional_param('addcategory', 0, PARAM_BOOL)) {
    require_sesskey();

    $categoryname = clean_param(required_param('categoryname', PARAM_TEXT), PARAM_TEXT);
    $categoryid = passionfinder_builder_make_identifier(optional_param('categoryid', '', PARAM_TEXT) ?: $categoryname);
    $prompt = clean_param(required_param('categoryprompt', PARAM_TEXT), PARAM_TEXT);
    $mostlabel = clean_param(optional_param('categorymostlabel', '', PARAM_TEXT), PARAM_TEXT);
    $leastlabel = clean_param(optional_param('categoryleastlabel', '', PARAM_TEXT), PARAM_TEXT);
    $shownegative = optional_param('shownegative', 0, PARAM_BOOL);

    if ($categoryid === '' || $categoryname === '' || $prompt === '') {
        $messages[] = ['message' => get_string('builderinvalidcategory', 'mod_passionfinder'), 'type' => 'error'];
    } else if (passionfinder_builder_category_exists($config, $categoryid)) {
        $messages[] = ['message' => get_string('builderduplicatecategory', 'mod_passionfinder'), 'type' => 'error'];
    } else {
        $category = new stdClass();
        $category->id = $categoryid;
        $category->name = $categoryname;
        $category->prompt = $prompt;
        $category->mostlabel = $mostlabel ?: $config->settings->mostlabel;
        $category->leastlabel = $leastlabel ?: $config->settings->leastlabel;
        $category->report = (object) [
            'showpositive' => (bool) $config->settings->showpositive,
            'shownegative' => (bool) $shownegative,
            'positivevisual' => $config->settings->positivevisual,
            'negativevisual' => $config->settings->negativevisual,
            'showdatatable' => true,
        ];
        $category->items = [];

        $config->categories[] = $category;
        passionfinder_builder_save_config($passionfinder->id, $config);
        $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
        $opencategoryid = $categoryid;
        $messages[] = ['message' => get_string('categorysaved', 'mod_passionfinder'), 'type' => 'success'];
    }
}

if (optional_param('updatecategory', 0, PARAM_BOOL)) {
    require_sesskey();

    $oldcategoryid = required_param('oldcategoryid', PARAM_TEXT);
    $categoryname = clean_param(required_param('categoryname', PARAM_TEXT), PARAM_TEXT);
    $newcategoryid = passionfinder_builder_make_identifier(optional_param('categoryid', '', PARAM_TEXT) ?: $categoryname);
    $prompt = clean_param(required_param('categoryprompt', PARAM_TEXT), PARAM_TEXT);
    $mostlabel = clean_param(optional_param('categorymostlabel', '', PARAM_TEXT), PARAM_TEXT);
    $leastlabel = clean_param(optional_param('categoryleastlabel', '', PARAM_TEXT), PARAM_TEXT);
    $shownegative = optional_param('shownegative', 0, PARAM_BOOL);

    $category = passionfinder_builder_get_category($config, $oldcategoryid);

    if (!$category || $newcategoryid === '' || $categoryname === '' || $prompt === '') {
        $messages[] = ['message' => get_string('builderinvalidcategory', 'mod_passionfinder'), 'type' => 'error'];
    } else if ($newcategoryid !== $oldcategoryid && passionfinder_builder_category_exists($config, $newcategoryid)) {
        $messages[] = ['message' => get_string('builderduplicatecategory', 'mod_passionfinder'), 'type' => 'error'];
    } else {
        $category->id = $newcategoryid;
        $category->name = $categoryname;
        $category->prompt = $prompt;
        $category->mostlabel = $mostlabel ?: $config->settings->mostlabel;
        $category->leastlabel = $leastlabel ?: $config->settings->leastlabel;
        if (empty($category->report) || !is_object($category->report)) {
            $category->report = new stdClass();
        }
        $category->report->showpositive = (bool) $config->settings->showpositive;
        $category->report->shownegative = (bool) $shownegative;
        $category->report->positivevisual = $config->settings->positivevisual;
        $category->report->negativevisual = $config->settings->negativevisual;
        $category->report->showdatatable = true;

        passionfinder_builder_replace_category($config, $oldcategoryid, $category);
        passionfinder_builder_save_config($passionfinder->id, $config);
        $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
        $opencategoryid = $newcategoryid;
        $messages[] = ['message' => get_string('categoryupdated', 'mod_passionfinder'), 'type' => 'success'];
    }
}

if (optional_param('deletecategory', 0, PARAM_BOOL)) {
    require_sesskey();

    $categoryid = required_param('categoryid', PARAM_TEXT);
    passionfinder_builder_delete_category($config, $categoryid);
    passionfinder_builder_save_config($passionfinder->id, $config);
    $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
    $opencategoryid = '';
    $messages[] = ['message' => get_string('categorydeleted', 'mod_passionfinder'), 'type' => 'success'];
}

if (optional_param('additem', 0, PARAM_BOOL)) {
    require_sesskey();

    $categoryid = required_param('categoryid', PARAM_TEXT);
    $category = passionfinder_builder_get_category($config, $categoryid);
    $itemlabel = clean_param(required_param('itemlabel', PARAM_TEXT), PARAM_TEXT);
    $itemid = passionfinder_builder_make_identifier(optional_param('itemid', '', PARAM_TEXT) ?: $itemlabel);

    if (!$category || $itemid === '' || $itemlabel === '') {
        $messages[] = ['message' => get_string('builderinvaliditem', 'mod_passionfinder'), 'type' => 'error'];
    } else if (passionfinder_builder_item_exists($category, $itemid)) {
        $messages[] = ['message' => get_string('builderduplicateitem', 'mod_passionfinder'), 'type' => 'error'];
    } else {
        $item = new stdClass();
        $item->id = $itemid;
        $item->label = $itemlabel;
        $category->items[] = $item;

        passionfinder_builder_replace_category($config, $categoryid, $category);
        passionfinder_builder_save_config($passionfinder->id, $config);
        $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
        $opencategoryid = $categoryid;
        $messages[] = ['message' => get_string('itemsaved', 'mod_passionfinder'), 'type' => 'success'];
    }
}

if (optional_param('updateitem', 0, PARAM_BOOL)) {
    require_sesskey();

    $categoryid = required_param('categoryid', PARAM_TEXT);
    $olditemid = required_param('olditemid', PARAM_TEXT);
    $category = passionfinder_builder_get_category($config, $categoryid);
    $itemlabel = clean_param(required_param('itemlabel', PARAM_TEXT), PARAM_TEXT);
    $newitemid = passionfinder_builder_make_identifier(optional_param('itemid', '', PARAM_TEXT) ?: $itemlabel);

    if (!$category || $newitemid === '' || $itemlabel === '') {
        $messages[] = ['message' => get_string('builderinvaliditem', 'mod_passionfinder'), 'type' => 'error'];
    } else if ($newitemid !== $olditemid && passionfinder_builder_item_exists($category, $newitemid)) {
        $messages[] = ['message' => get_string('builderduplicateitem', 'mod_passionfinder'), 'type' => 'error'];
    } else {
        foreach ($category->items as $item) {
            if ($item->id === $olditemid) {
                $item->id = $newitemid;
                $item->label = $itemlabel;
                break;
            }
        }

        passionfinder_builder_replace_category($config, $categoryid, $category);
        passionfinder_builder_save_config($passionfinder->id, $config);
        $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
        $opencategoryid = $categoryid;
        $edititemid = $newitemid;
        $messages[] = ['message' => get_string('itemupdated', 'mod_passionfinder'), 'type' => 'success'];
    }
}

if (optional_param('deleteitem', 0, PARAM_BOOL)) {
    require_sesskey();

    $categoryid = required_param('categoryid', PARAM_TEXT);
    $itemid = required_param('itemid', PARAM_TEXT);
    $category = passionfinder_builder_get_category($config, $categoryid);

    if ($category) {
        $category->items = array_values(array_filter($category->items, static function($item) use ($itemid): bool {
            return $item->id !== $itemid;
        }));
        passionfinder_builder_replace_category($config, $categoryid, $category);
        passionfinder_builder_save_config($passionfinder->id, $config);
        $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
    }

    $opencategoryid = $categoryid;
    $messages[] = ['message' => get_string('itemdeleted', 'mod_passionfinder'), 'type' => 'success'];
}

$config = passionfinder_builder_get_config($passionfinder);
$config = passionfinder_builder_normalise_config($config, $passionfinder);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($passionfinder->name));
echo $OUTPUT->heading(get_string('instrumentbuilder', 'mod_passionfinder'), 3);

foreach ($messages as $message) {
    echo $OUTPUT->notification($message['message'], $message['type']);
}

try {
    instrument_parser::parse(json_encode($config), $passionfinder);
    echo $OUTPUT->notification(get_string('builderjsonvalid', 'mod_passionfinder'), 'success');
} catch (moodle_exception $exception) {
    echo $OUTPUT->notification(get_string('builderjsonwarning', 'mod_passionfinder', $exception->getMessage()), 'warning');
}

$summarytable = new html_table();
$summarytable->attributes['class'] = 'generaltable passionfinder-builder-summary';
$summarytable->head = [get_string('builderitem', 'mod_passionfinder'), get_string('builderstatus', 'mod_passionfinder')];
$summarytable->data[] = [get_string('instrumenttitle', 'mod_passionfinder'), s($config->metadata->title)];
$summarytable->data[] = [get_string('categoryplural', 'mod_passionfinder'), count($config->categories)];
$summarytable->data[] = [get_string('builderitems', 'mod_passionfinder'), passionfinder_builder_count_items($config)];
echo html_writer::table($summarytable);

echo passionfinder_builder_render_import_export_panel($formurl, $config);

echo passionfinder_builder_render_metadata_form($formurl, $config, $passionfinder);

echo $OUTPUT->heading(get_string('categories', 'mod_passionfinder'), 4);

if (empty($config->categories)) {
    echo $OUTPUT->notification(get_string('nocategoriesyet', 'mod_passionfinder'), 'info');
} else {
    foreach ($config->categories as $category) {
        $detailsattrs = [
            'id' => 'category-' . $category->id,
            'class' => 'passionfinder-builder-details passionfinder-category-details',
        ];

        if ($category->id === $opencategoryid) {
            $detailsattrs['open'] = 'open';
        }

        echo html_writer::start_tag('details', $detailsattrs);
        $itemlabel = count($category->items) === 1 ? get_string('builderitem', 'mod_passionfinder') : get_string('builderitems', 'mod_passionfinder');
        echo html_writer::tag('summary', s($category->name) . ' (' . count($category->items) . ' ' . $itemlabel . ')', ['class' => 'btn btn-info btn-block text-left passionfinder-category-summary']);
        echo passionfinder_builder_render_delete_category_form($formurl, $category);

        echo $OUTPUT->box_start('generalbox passionfinder-builder-category');
        echo html_writer::tag('p', s($category->prompt));
        echo html_writer::tag('p', get_string('categoryid', 'mod_passionfinder') . ': ' . s($category->id), ['class' => 'text-muted']);

        echo passionfinder_builder_render_category_form($formurl, $category);
        echo passionfinder_builder_render_items($formurl, $category, $edititemid);
        echo passionfinder_builder_render_add_item_form($formurl, $category);
        echo $OUTPUT->box_end();
        echo html_writer::end_tag('details');
    }
}

echo passionfinder_builder_render_add_category_form($formurl, $config);

$viewurl = new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id]);
$settingsurl = new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]);

echo html_writer::div(
    html_writer::link($viewurl, get_string('viewactivity', 'mod_passionfinder'), ['class' => 'btn btn-secondary']) . ' ' .
    html_writer::link($settingsurl, get_string('editsettings', 'mod_passionfinder'), ['class' => 'btn btn-secondary']),
    'passionfinder-actions mt-3'
);

echo $OUTPUT->footer();

/**
 * Gets uploaded JSON content from a file input.
 *
 * @param string $fieldname File input name.
 * @return string|null Uploaded JSON text, or null if no file was uploaded.
 */
function passionfinder_builder_get_uploaded_json(string $fieldname): ?string {
    if (empty($_FILES[$fieldname]) || empty($_FILES[$fieldname]['tmp_name'])) {
        return null;
    }

    if (!isset($_FILES[$fieldname]['error']) || $_FILES[$fieldname]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$fieldname]['error'] !== UPLOAD_ERR_OK) {
        throw new moodle_exception('builderimportuploaderror', 'mod_passionfinder');
    }

    if (!is_uploaded_file($_FILES[$fieldname]['tmp_name'])) {
        throw new moodle_exception('builderimportuploaderror', 'mod_passionfinder');
    }

    $filesize = (int) ($_FILES[$fieldname]['size'] ?? 0);

    if ($filesize <= 0 || $filesize > 524288) {
        throw new moodle_exception('builderimportfilesize', 'mod_passionfinder');
    }

    $content = file_get_contents($_FILES[$fieldname]['tmp_name']);

    if ($content === false) {
        throw new moodle_exception('builderimportuploaderror', 'mod_passionfinder');
    }

    return trim($content);
}

/**
 * Gets the JSON config from the activity.
 *
 * @param stdClass $passionfinder Activity instance.
 * @return stdClass
 */
function passionfinder_builder_get_config(stdClass $passionfinder): stdClass {
    $config = json_decode($passionfinder->instrumentjson ?? '');

    if (json_last_error() === JSON_ERROR_NONE && is_object($config)) {
        return $config;
    }

    return new stdClass();
}

/**
 * Normalises the builder config object.
 *
 * @param stdClass $config Config object.
 * @param stdClass $passionfinder Activity instance.
 * @return stdClass
 */
function passionfinder_builder_normalise_config(stdClass $config, stdClass $passionfinder): stdClass {
    if (empty($config->metadata) || !is_object($config->metadata)) {
        $config->metadata = new stdClass();
    }

    $config->metadata->schema = 'mod_passionfinder';
    $config->metadata->schemaVersion = 1;
    $config->metadata->title = $config->metadata->title ?? $passionfinder->name;
    $config->metadata->description = $config->metadata->description ?? '';
    $config->metadata->createdBy = $config->metadata->createdBy ?? 'mod_passionfinder_builder';
    $config->metadata->containsStudentData = false;

    if (empty($config->settings) || !is_object($config->settings)) {
        $config->settings = new stdClass();
    }

    $config->settings->itemsperround = (int) ($config->settings->itemsperround ?? $passionfinder->itemsperround ?? 5);
    $config->settings->roundspercategory = (int) ($config->settings->roundspercategory ?? $passionfinder->roundspercategory ?? 8);
    $config->settings->resultdepth = (int) ($config->settings->resultdepth ?? $passionfinder->resultdepth ?? 5);
    $config->settings->mostlabel = $config->settings->mostlabel ?? $passionfinder->mostlabel ?? get_string('defaultmostlabel', 'mod_passionfinder');
    $config->settings->leastlabel = $config->settings->leastlabel ?? $passionfinder->leastlabel ?? get_string('defaultleastlabel', 'mod_passionfinder');
    $config->settings->showpositive = property_exists($config->settings, 'showpositive') ? (bool) $config->settings->showpositive : true;
    $config->settings->shownegative = property_exists($config->settings, 'shownegative') ? (bool) $config->settings->shownegative : true;
    $config->settings->positivevisual = passionfinder_builder_clean_visual($config->settings->positivevisual ?? 'hearts');
    $config->settings->negativevisual = passionfinder_builder_clean_visual($config->settings->negativevisual ?? 'bubbles');

    if (empty($config->categories) || !is_array($config->categories)) {
        $config->categories = [];
    }

    foreach ($config->categories as $category) {
        if (!isset($category->items) || !is_array($category->items)) {
            $category->items = [];
        }
        if (empty($category->report) || !is_object($category->report)) {
            $category->report = new stdClass();
        }
        $category->report->showpositive = property_exists($category->report, 'showpositive') ? (bool) $category->report->showpositive : (bool) $config->settings->showpositive;
        $category->report->shownegative = property_exists($category->report, 'shownegative') ? (bool) $category->report->shownegative : (bool) $config->settings->shownegative;
        $category->report->positivevisual = passionfinder_builder_clean_visual($category->report->positivevisual ?? $config->settings->positivevisual);
        $category->report->negativevisual = passionfinder_builder_clean_visual($category->report->negativevisual ?? $config->settings->negativevisual);
        $category->report->showdatatable = property_exists($category->report, 'showdatatable') ? (bool) $category->report->showdatatable : true;
    }

    return $config;
}

/**
 * Saves config to the activity instrumentjson field.
 *
 * @param int $passionfinderid Activity id.
 * @param stdClass $config Config object.
 * @return void
 */
function passionfinder_builder_save_config(int $passionfinderid, stdClass $config): void {
    global $DB;

    $record = new stdClass();
    $record->id = $passionfinderid;
    $record->instrumentjson = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $record->timemodified = time();

    $DB->update_record('passionfinder', $record);
}

/**
 * Renders import/export panel.
 *
 * @param moodle_url $formurl Form URL.
 * @param stdClass $config Config object.
 * @return string
 */
function passionfinder_builder_render_import_export_panel(moodle_url $formurl, stdClass $config): string {
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $html = '';
    $html .= html_writer::start_tag('details', ['class' => 'passionfinder-builder-details', 'open' => 'open']);
    $html .= html_writer::tag('summary', get_string('importexportjson', 'mod_passionfinder'), ['class' => 'btn btn-secondary btn-block text-left']);
    $html .= html_writer::start_div('generalbox passionfinder-builder-importexport');

    $html .= html_writer::tag('p', get_string('importexportintro', 'mod_passionfinder'));

    $html .= html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl, 'class' => 'mb-3']);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'exportjson', 'value' => 1]);
    $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('exportjson', 'mod_passionfinder'), 'class' => 'btn btn-primary']);
    $html .= html_writer::end_tag('form');

    $html .= html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $formurl,
        'enctype' => 'multipart/form-data',
    ]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'importjson', 'value' => 1]);

    $html .= html_writer::start_div('mb-3');
    $html .= html_writer::tag('label', get_string('jsonfile', 'mod_passionfinder'), ['for' => 'id_importjsonfile']);
    $html .= html_writer::empty_tag('input', [
        'type' => 'file',
        'id' => 'id_importjsonfile',
        'name' => 'importjsonfile',
        'accept' => '.json,application/json',
        'class' => 'form-control',
    ]);
    $html .= html_writer::end_div();

    $html .= html_writer::tag('label', get_string('pastejson', 'mod_passionfinder'), ['for' => 'id_importjsontext']);
    $html .= html_writer::tag('textarea', '', [
        'id' => 'id_importjsontext',
        'name' => 'importjsontext',
        'rows' => 10,
        'cols' => 90,
        'class' => 'form-control',
    ]);
    $html .= html_writer::tag('p', get_string('currentjsonhint', 'mod_passionfinder'), ['class' => 'form-text text-muted']);
    $html .= html_writer::tag('details',
        html_writer::tag('summary', get_string('showcurrentjson', 'mod_passionfinder')) .
        html_writer::tag('pre', s($json), ['class' => 'mt-2 p-3 bg-light border'])
    );
    $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('importjson', 'mod_passionfinder'), 'class' => 'btn btn-warning mt-2']);
    $html .= html_writer::end_tag('form');

    $html .= html_writer::end_div();
    $html .= html_writer::end_tag('details');

    return $html;
}

/**
 * Renders metadata form.
 *
 * @param moodle_url $formurl Form URL.
 * @param stdClass $config Config object.
 * @param stdClass $passionfinder Activity instance.
 * @return string
 */
function passionfinder_builder_render_metadata_form(moodle_url $formurl, stdClass $config, stdClass $passionfinder): string {
    $html = '';
    $html .= html_writer::start_tag('details', ['class' => 'passionfinder-builder-details']);
    $html .= html_writer::tag('summary', get_string('editinstrumentsettings', 'mod_passionfinder'), ['class' => 'btn btn-primary btn-block text-left']);

    $html .= html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'saveinstrumentmeta', 'value' => 1]);

    $html .= passionfinder_builder_text_input('instrumenttitle', get_string('instrumenttitle', 'mod_passionfinder'), $config->metadata->title, true);
    $html .= passionfinder_builder_textarea('instrumentdescription', get_string('instrumentdescription', 'mod_passionfinder'), $config->metadata->description, false);

    $html .= html_writer::start_div('mb-3');
    $html .= html_writer::tag('label', get_string('itemsperround', 'mod_passionfinder'));
    $html .= html_writer::select([3 => 3, 4 => 4, 5 => 5, 6 => 6], 'itemsperround', $config->settings->itemsperround, false, ['class' => 'form-control']);
    $html .= html_writer::end_div();

    $roundoptions = [4 => 4, 6 => 6, 8 => 8, 10 => 10, 12 => 12, 16 => 16];
    $html .= html_writer::start_div('mb-3');
    $html .= html_writer::tag('label', get_string('roundspercategory', 'mod_passionfinder'));
    $html .= html_writer::select($roundoptions, 'roundspercategory', $config->settings->roundspercategory, false, ['class' => 'form-control']);
    $html .= html_writer::end_div();

    $html .= html_writer::start_div('mb-3');
    $html .= html_writer::tag('label', get_string('resultdepth', 'mod_passionfinder'));
    $html .= html_writer::select([3 => 3, 5 => 5, 10 => 10], 'resultdepth', $config->settings->resultdepth, false, ['class' => 'form-control']);
    $html .= html_writer::end_div();

    $html .= passionfinder_builder_text_input('mostlabel', get_string('mostlabel', 'mod_passionfinder'), $config->settings->mostlabel, true);
    $html .= passionfinder_builder_text_input('leastlabel', get_string('leastlabel', 'mod_passionfinder'), $config->settings->leastlabel, true);

    $html .= html_writer::tag('h5', get_string('visualreportsettings', 'mod_passionfinder'));
    $html .= html_writer::tag('p', get_string('visualreportsettings_help', 'mod_passionfinder'), ['class' => 'form-text text-muted']);
    $html .= passionfinder_builder_checkbox('showpositive', get_string('showpositivevisual', 'mod_passionfinder'), (bool) $config->settings->showpositive);
    $html .= passionfinder_builder_visual_select('positivevisual', get_string('positivevisual', 'mod_passionfinder'), $config->settings->positivevisual);
    $html .= passionfinder_builder_checkbox('shownegative', get_string('shownegativevisual', 'mod_passionfinder'), (bool) $config->settings->shownegative);
    $html .= passionfinder_builder_visual_select('negativevisual', get_string('negativevisual', 'mod_passionfinder'), $config->settings->negativevisual);

    $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('save'), 'class' => 'btn btn-primary']);
    $html .= html_writer::end_tag('form');
    $html .= html_writer::end_tag('details');

    return $html;
}

/**
 * Renders category edit form.
 *
 * @param moodle_url $formurl Form URL.
 * @param stdClass $category Category object.
 * @return string
 */
function passionfinder_builder_render_category_form(moodle_url $formurl, stdClass $category): string {
    $shownegative = !empty($category->report->shownegative);

    $html = '';
    $html .= html_writer::start_tag('details', ['class' => 'passionfinder-builder-details']);
    $html .= html_writer::tag('summary', get_string('editcategory', 'mod_passionfinder'));

    $html .= html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl . '#category-' . $category->id]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatecategory', 'value' => 1]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'oldcategoryid', 'value' => s($category->id)]);

    $html .= passionfinder_builder_text_input('categoryname', get_string('categoryname', 'mod_passionfinder'), $category->name, true);
    $html .= passionfinder_builder_text_input('categoryid', get_string('categoryid', 'mod_passionfinder'), $category->id, false);
    $html .= passionfinder_builder_textarea('categoryprompt', get_string('categoryprompt', 'mod_passionfinder'), $category->prompt, true);
    $html .= passionfinder_builder_text_input('categorymostlabel', get_string('mostlabel', 'mod_passionfinder'), $category->mostlabel, false);
    $html .= passionfinder_builder_text_input('categoryleastlabel', get_string('leastlabel', 'mod_passionfinder'), $category->leastlabel, false);
    $html .= passionfinder_builder_checkbox('shownegative', get_string('shownegative', 'mod_passionfinder'), $shownegative);

    $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('save'), 'class' => 'btn btn-primary']);
    $html .= html_writer::end_tag('form');
    $html .= html_writer::end_tag('details');

    return $html;
}

/**
 * Renders item edit forms.
 *
 * @param moodle_url $formurl Form URL.
 * @param stdClass $category Category object.
 * @param string $edititemid Open item id.
 * @return string
 */
function passionfinder_builder_render_items(moodle_url $formurl, stdClass $category, string $edititemid): string {
    if (empty($category->items)) {
        return html_writer::div(get_string('noitemsincategory', 'mod_passionfinder'), 'alert alert-info');
    }

    $html = '';
    foreach ($category->items as $item) {
        $attrs = [
            'id' => 'edit-item-' . $category->id . '-' . $item->id,
            'class' => 'passionfinder-builder-details passionfinder-edit-item-details ml-3 mb-2',
        ];

        if ($item->id === $edititemid) {
            $attrs['open'] = 'open';
        }

        $html .= html_writer::start_tag('details', $attrs);
        $html .= html_writer::tag('summary', '▾ ' . s($item->label), ['class' => 'btn btn-outline-info btn-block text-left passionfinder-item-summary passionfinder-existing-item-summary']);
        $html .= passionfinder_builder_render_delete_item_form($formurl, $category, $item);

        $html .= html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl . '#category-' . $category->id]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updateitem', 'value' => 1]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'value' => s($category->id)]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'olditemid', 'value' => s($item->id)]);

        $html .= passionfinder_builder_text_input('itemlabel', get_string('itemlabel', 'mod_passionfinder'), $item->label, true);
        $html .= passionfinder_builder_text_input('itemid', get_string('itemid', 'mod_passionfinder'), $item->id, false);

        $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('save'), 'class' => 'btn btn-primary']);
        $html .= html_writer::end_tag('form');

        $html .= html_writer::end_tag('details');
    }

    return $html;
}

/**
 * Renders add category form.
 *
 * @param moodle_url $formurl Form URL.
 * @param stdClass $config Config object.
 * @return string
 */
function passionfinder_builder_render_add_category_form(moodle_url $formurl, stdClass $config): string {
    $html = '';
    $html .= html_writer::start_tag('details', ['id' => 'passionfinder-add-category', 'class' => 'passionfinder-builder-details']);
    $html .= html_writer::tag('summary', '+ ' . get_string('addcategory', 'mod_passionfinder'), ['class' => 'btn btn-primary btn-block text-left']);

    $html .= html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl . '#passionfinder-add-category']);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'addcategory', 'value' => 1]);

    $html .= passionfinder_builder_text_input('categoryname', get_string('categoryname', 'mod_passionfinder'), '', true);
    $html .= passionfinder_builder_text_input('categoryid', get_string('categoryid', 'mod_passionfinder'), '', false);
    $html .= passionfinder_builder_textarea('categoryprompt', get_string('categoryprompt', 'mod_passionfinder'), '', true);
    $html .= passionfinder_builder_text_input('categorymostlabel', get_string('mostlabel', 'mod_passionfinder'), $config->settings->mostlabel, false);
    $html .= passionfinder_builder_text_input('categoryleastlabel', get_string('leastlabel', 'mod_passionfinder'), $config->settings->leastlabel, false);
    $html .= passionfinder_builder_checkbox('shownegative', get_string('shownegative', 'mod_passionfinder'), false);

    $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('save'), 'class' => 'btn btn-primary']);
    $html .= html_writer::end_tag('form');
    $html .= html_writer::end_tag('details');

    return $html;
}

/**
 * Renders add item form.
 *
 * @param moodle_url $formurl Form URL.
 * @param stdClass $category Category object.
 * @return string
 */
function passionfinder_builder_render_add_item_form(moodle_url $formurl, stdClass $category): string {
    $html = '';
    $html .= html_writer::start_tag('details', ['class' => 'passionfinder-builder-details passionfinder-add-item-details ml-3 mb-2']);
    $html .= html_writer::tag('summary', '+ ' . get_string('additemtocategory', 'mod_passionfinder', s($category->name)), ['class' => 'btn btn-outline-info btn-block text-left passionfinder-item-summary']);

    $html .= html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl . '#category-' . $category->id]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'additem', 'value' => 1]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'value' => s($category->id)]);

    $html .= passionfinder_builder_text_input('itemlabel', get_string('itemlabel', 'mod_passionfinder'), '', true);
    $html .= passionfinder_builder_text_input('itemid', get_string('itemid', 'mod_passionfinder'), '', false);

    $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('save'), 'class' => 'btn btn-primary']);
    $html .= html_writer::end_tag('form');
    $html .= html_writer::end_tag('details');

    return $html;
}

/**
 * Renders delete category form.
 *
 * @param moodle_url $formurl Form URL.
 * @param stdClass $category Category object.
 * @return string
 */
function passionfinder_builder_render_delete_category_form(moodle_url $formurl, stdClass $category): string {
    $confirm = get_string('deletecategoryconfirm', 'mod_passionfinder', s($category->name));

    $html = '';
    $html .= html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $formurl,
        'class' => 'passionfinder-delete-form passionfinder-category-delete-form',
        'onsubmit' => 'return confirm(' . json_encode($confirm) . ');',
    ]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'deletecategory', 'value' => 1]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'value' => s($category->id)]);
    $html .= html_writer::tag('button', '🗑', [
        'type' => 'submit',
        'class' => 'btn btn-link passionfinder-delete-button',
        'title' => get_string('deletecategory', 'mod_passionfinder'),
        'aria-label' => get_string('deletecategory', 'mod_passionfinder'),
    ]);
    $html .= html_writer::end_tag('form');

    return $html;
}

/**
 * Renders delete item form.
 *
 * @param moodle_url $formurl Form URL.
 * @param stdClass $category Category object.
 * @param stdClass $item Item object.
 * @return string
 */
function passionfinder_builder_render_delete_item_form(moodle_url $formurl, stdClass $category, stdClass $item): string {
    $confirm = get_string('deleteitemconfirm', 'mod_passionfinder');

    $html = '';
    $html .= html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $formurl . '#category-' . $category->id,
        'class' => 'passionfinder-delete-form passionfinder-item-delete-form',
        'onsubmit' => 'return confirm(' . json_encode($confirm) . ');',
    ]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'deleteitem', 'value' => 1]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'value' => s($category->id)]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'itemid', 'value' => s($item->id)]);
    $html .= html_writer::tag('button', '🗑', [
        'type' => 'submit',
        'class' => 'btn btn-link passionfinder-delete-button',
        'title' => get_string('deleteitem', 'mod_passionfinder'),
        'aria-label' => get_string('deleteitem', 'mod_passionfinder'),
    ]);
    $html .= html_writer::end_tag('form');

    return $html;
}

/**
 * Text input helper.
 *
 * @param string $name Input name.
 * @param string $label Label.
 * @param string $value Value.
 * @param bool $required Required.
 * @return string HTML.
 */
function passionfinder_builder_text_input(string $name, string $label, string $value, bool $required): string {
    $attrs = ['type' => 'text', 'name' => $name, 'class' => 'form-control', 'maxlength' => 255, 'value' => s($value)];

    if ($required) {
        $attrs['required'] = 'required';
    }

    return html_writer::start_div('mb-3') .
        html_writer::tag('label', $label) .
        html_writer::empty_tag('input', $attrs) .
        html_writer::end_div();
}

/**
 * Textarea helper.
 *
 * @param string $name Input name.
 * @param string $label Label.
 * @param string $value Value.
 * @param bool $required Required.
 * @return string HTML.
 */
function passionfinder_builder_textarea(string $name, string $label, string $value, bool $required): string {
    $attrs = ['name' => $name, 'rows' => 3, 'cols' => 80, 'class' => 'form-control'];

    if ($required) {
        $attrs['required'] = 'required';
    }

    return html_writer::start_div('mb-3') .
        html_writer::tag('label', $label) .
        html_writer::tag('textarea', s($value), $attrs) .
        html_writer::end_div();
}

/**
 * Visual select helper.
 *
 * @param string $name Input name.
 * @param string $label Label.
 * @param string $selected Selected value.
 * @return string HTML.
 */
function passionfinder_builder_visual_select(string $name, string $label, string $selected): string {
    $options = [
        'hearts' => get_string('visualhearts', 'mod_passionfinder'),
        'bubbles' => get_string('visualbubbles', 'mod_passionfinder'),
    ];

    return html_writer::start_div('mb-3') .
        html_writer::tag('label', $label) .
        html_writer::select($options, $name, passionfinder_builder_clean_visual($selected), false, ['class' => 'form-control']) .
        html_writer::end_div();
}

/**
 * Cleans a visual setting.
 *
 * @param string $value Raw value.
 * @return string Safe visual setting.
 */
function passionfinder_builder_clean_visual(string $value): string {
    $value = strtolower(trim($value));

    if (!in_array($value, ['hearts', 'bubbles'], true)) {
        return 'hearts';
    }

    return $value;
}

/**
 * Checkbox helper.
 *
 * @param string $name Input name.
 * @param string $label Label.
 * @param bool $checked Checked.
 * @return string HTML.
 */
function passionfinder_builder_checkbox(string $name, string $label, bool $checked): string {
    $attrs = ['type' => 'checkbox', 'name' => $name, 'value' => 1];

    if ($checked) {
        $attrs['checked'] = 'checked';
    }

    return html_writer::start_div('mb-3 form-check') .
        html_writer::empty_tag('input', $attrs) . ' ' .
        html_writer::tag('label', $label) .
        html_writer::end_div();
}

/**
 * Gets a category by id.
 *
 * @param stdClass $config Config object.
 * @param string $categoryid Category id.
 * @return stdClass|null
 */
function passionfinder_builder_get_category(stdClass $config, string $categoryid): ?stdClass {
    foreach ($config->categories as $category) {
        if ($category->id === $categoryid) {
            return $category;
        }
    }

    return null;
}

/**
 * Replaces a category.
 *
 * @param stdClass $config Config object.
 * @param string $oldcategoryid Old id.
 * @param stdClass $replacement Replacement.
 * @return void
 */
function passionfinder_builder_replace_category(stdClass $config, string $oldcategoryid, stdClass $replacement): void {
    foreach ($config->categories as $index => $category) {
        if ($category->id === $oldcategoryid) {
            $config->categories[$index] = $replacement;
            return;
        }
    }
}

/**
 * Deletes a category.
 *
 * @param stdClass $config Config object.
 * @param string $categoryid Category id.
 * @return void
 */
function passionfinder_builder_delete_category(stdClass $config, string $categoryid): void {
    $config->categories = array_values(array_filter($config->categories, static function($category) use ($categoryid): bool {
        return $category->id !== $categoryid;
    }));
}

/**
 * Checks whether a category exists.
 *
 * @param stdClass $config Config object.
 * @param string $categoryid Category id.
 * @return bool
 */
function passionfinder_builder_category_exists(stdClass $config, string $categoryid): bool {
    return passionfinder_builder_get_category($config, $categoryid) !== null;
}

/**
 * Checks whether an item exists in a category.
 *
 * @param stdClass $category Category object.
 * @param string $itemid Item id.
 * @return bool
 */
function passionfinder_builder_item_exists(stdClass $category, string $itemid): bool {
    foreach ($category->items as $item) {
        if ($item->id === $itemid) {
            return true;
        }
    }

    return false;
}

/**
 * Counts all items.
 *
 * @param stdClass $config Config object.
 * @return int Item count.
 */
function passionfinder_builder_count_items(stdClass $config): int {
    $count = 0;

    foreach ($config->categories as $category) {
        $count += count($category->items ?? []);
    }

    return $count;
}

/**
 * Creates a safe identifier.
 *
 * @param string $value Raw value.
 * @return string Safe id.
 */
function passionfinder_builder_make_identifier(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    $value = trim($value, '_');

    return substr($value, 0, 100);
}
