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
 * View page for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_passionfinder\local\attempt_manager;
use mod_passionfinder\local\report_builder;
use mod_passionfinder\local\scoring;

$id = optional_param('id', 0, PARAM_INT);
$n = optional_param('n', 0, PARAM_INT);
$setnumber = optional_param('set', 1, PARAM_INT);
$showreport = optional_param('report', 0, PARAM_BOOL);

if ($id) {
    $cm = get_coursemodule_from_id('passionfinder', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $passionfinder = $DB->get_record('passionfinder', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $passionfinder->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('passionfinder', $passionfinder->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/passionfinder:view', $context);

$PAGE->set_url('/mod/passionfinder/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($passionfinder->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$messages = [];

if (trim((string) $passionfinder->instrumentjson) === '') {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($passionfinder->name));

    if (!empty($passionfinder->intro)) {
        echo $OUTPUT->box(format_module_intro('passionfinder', $passionfinder, $cm->id), 'generalbox mod_introbox');
    }

    if (has_capability('mod/passionfinder:viewreports', $context)) {
        $reporturl = new moodle_url('/mod/passionfinder/report.php', ['id' => $cm->id]);
        echo html_writer::div(
            html_writer::link($reporturl, get_string('reports', 'mod_passionfinder'), ['class' => 'btn btn-primary']),
            'passionfinder-actions mb-3'
        );
    }

    echo $OUTPUT->notification(get_string('noinstrumentjson', 'mod_passionfinder'), 'info');
    echo $OUTPUT->footer();
    exit;
}

try {
    $attempt = attempt_manager::get_or_create_attempt($passionfinder, $cm, (int) $USER->id);
} catch (moodle_exception $exception) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($passionfinder->name));
    echo $OUTPUT->notification(get_string('parsererrorheading', 'mod_passionfinder'), 'error');
    echo $OUTPUT->box(s($exception->getMessage()), 'generalbox');
    echo $OUTPUT->footer();
    exit;
}

$attemptsets = attempt_manager::get_attempt_sets((int) $attempt->id);
$totalsets = count($attemptsets);

if ($totalsets < 1) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($passionfinder->name));
    echo $OUTPUT->notification(get_string('nosetsavailable', 'mod_passionfinder'), 'error');
    echo $OUTPUT->footer();
    exit;
}

$setnumber = max(1, min($setnumber, $totalsets));
$currentset = attempt_manager::get_set_by_number((int) $attempt->id, $setnumber);

if (!$currentset || empty($currentset->setdata)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($passionfinder->name));
    echo $OUTPUT->notification(get_string('setnotavailable', 'mod_passionfinder'), 'error');
    echo $OUTPUT->footer();
    exit;
}

if (optional_param('savechoice', 0, PARAM_BOOL)) {
    require_sesskey();
    require_capability('mod/passionfinder:submit', $context);

    $mostitemid = required_param('mostitemid', PARAM_ALPHANUMEXT);
    $leastitemid = required_param('leastitemid', PARAM_ALPHANUMEXT);

    try {
        attempt_manager::save_choice($attempt, $currentset, $mostitemid, $leastitemid);
        $messages[] = [
            'message' => get_string('choicesaved', 'mod_passionfinder'),
            'type' => 'success',
        ];

        if ($setnumber < $totalsets) {
            redirect(new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id, 'set' => $setnumber + 1]));
        }
    } catch (moodle_exception $exception) {
        $messages[] = [
            'message' => $exception->getMessage(),
            'type' => 'error',
        ];
    }
}

if (optional_param('completeattempt', 0, PARAM_BOOL)) {
    require_sesskey();
    require_capability('mod/passionfinder:submit', $context);

    try {
        $attempt = attempt_manager::complete_attempt($attempt);
        scoring::calculate_and_store((int) $attempt->id);

        redirect(new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id, 'report' => 1]));
    } catch (moodle_exception $exception) {
        $messages[] = [
            'message' => $exception->getMessage(),
            'type' => 'error',
        ];
    }
}

$choicesbyset = attempt_manager::get_choices_by_set((int) $attempt->id);
$progress = attempt_manager::get_progress((int) $attempt->id);
$currentchoice = $choicesbyset[(int) $currentset->id] ?? null;

if ($attempt->status === attempt_manager::STATUS_COMPLETED || $showreport) {
    $reportresults = scoring::calculate_and_store((int) $attempt->id);
    passionfinder_render_report_page($OUTPUT, $passionfinder, $cm, $attempt, $reportresults);
    exit;
}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($passionfinder->name));

if (!empty($passionfinder->intro)) {
    echo $OUTPUT->box(format_module_intro('passionfinder', $passionfinder, $cm->id), 'generalbox mod_introbox');
}

foreach ($messages as $message) {
    echo $OUTPUT->notification($message['message'], $message['type']);
}

if (has_capability('mod/passionfinder:viewreports', $context)) {
    echo $OUTPUT->box_start('generalbox passionfinder-teacher-summary');
    echo $OUTPUT->heading(get_string('pluginadministration', 'mod_passionfinder'), 3);

    $reporturl = new moodle_url('/mod/passionfinder/report.php', ['id' => $cm->id]);
    echo html_writer::div(
        html_writer::link(
            $reporturl,
            get_string('reports', 'mod_passionfinder'),
            ['class' => 'btn btn-primary']
        ),
        'passionfinder-actions'
    );

    echo $OUTPUT->box_end();
}

echo $OUTPUT->heading(get_string('attemptheading', 'mod_passionfinder'), 3);

$progresshtml = get_string('progresssummary', 'mod_passionfinder', (object) [
    'answered' => $progress->answeredsets,
    'total' => $progress->totalsets,
    'remaining' => $progress->remainingsets,
]);
echo html_writer::tag('p', s($progresshtml), ['class' => 'alert alert-info']);

echo html_writer::tag('h4', get_string('setnumberoftotal', 'mod_passionfinder', (object) [
    'number' => $setnumber,
    'total' => $totalsets,
]) . ': ' . s($currentset->setdata->categoryname));

echo html_writer::tag('p', s($currentset->setdata->prompt));

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id, 'set' => $setnumber]),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savechoice', 'value' => 1]);

$table = new html_table();
$table->attributes['class'] = 'generaltable passionfinder-choice-table';
$table->head = [
    s($currentset->setdata->mostlabel),
    get_string('itemlabel', 'mod_passionfinder'),
    s($currentset->setdata->leastlabel),
];

foreach ($currentset->setdata->items as $item) {
    $mostattrs = [
        'type' => 'radio',
        'name' => 'mostitemid',
        'value' => s($item->id),
        'required' => 'required',
    ];
    $leastattrs = [
        'type' => 'radio',
        'name' => 'leastitemid',
        'value' => s($item->id),
        'required' => 'required',
    ];

    if ($currentchoice && $currentchoice->mostitemid === $item->id) {
        $mostattrs['checked'] = 'checked';
    }

    if ($currentchoice && $currentchoice->leastitemid === $item->id) {
        $leastattrs['checked'] = 'checked';
    }

    $table->data[] = [
        html_writer::empty_tag('input', $mostattrs),
        s($item->label),
        html_writer::empty_tag('input', $leastattrs),
    ];
}

echo html_writer::table($table);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => $setnumber < $totalsets ? get_string('saveandcontinue', 'mod_passionfinder') : get_string('savechoice', 'mod_passionfinder'),
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

$navlinks = [];

if ($setnumber > 1) {
    $navlinks[] = html_writer::link(
        new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id, 'set' => $setnumber - 1]),
        get_string('previousset', 'mod_passionfinder'),
        ['class' => 'btn btn-secondary']
    );
}

if ($setnumber < $totalsets) {
    $navlinks[] = html_writer::link(
        new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id, 'set' => $setnumber + 1]),
        get_string('nextset', 'mod_passionfinder'),
        ['class' => 'btn btn-secondary']
    );
}

if (!empty($navlinks)) {
    echo html_writer::div(implode(' ', $navlinks), 'passionfinder-navigation mt-3');
}

echo $OUTPUT->heading(get_string('reviewheading', 'mod_passionfinder'), 4);

$reviewtable = new html_table();
$reviewtable->attributes['class'] = 'generaltable passionfinder-review-table';
$reviewtable->head = [
    get_string('set', 'mod_passionfinder'),
    get_string('category', 'mod_passionfinder'),
    get_string('status', 'mod_passionfinder'),
    get_string('action', 'mod_passionfinder'),
];

foreach ($attemptsets as $setrecord) {
    $choice = $choicesbyset[(int) $setrecord->id] ?? null;
    $setdata = $setrecord->setdata;

    $status = $choice ? get_string('answered', 'mod_passionfinder') : get_string('notansweredyet', 'mod_passionfinder');
    $categoryname = $setdata && !empty($setdata->categoryname) ? $setdata->categoryname : $setrecord->categoryid;

    $reviewtable->data[] = [
        (int) $setrecord->setnumber,
        s($categoryname),
        $status,
        html_writer::link(
            new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id, 'set' => $setrecord->setnumber]),
            get_string('reviewedit', 'mod_passionfinder')
        ),
    ];
}

echo html_writer::table($reviewtable);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id, 'set' => $setnumber]),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'completeattempt', 'value' => 1]);

$submitattrs = [
    'type' => 'submit',
    'value' => get_string('submitattempt', 'mod_passionfinder'),
    'class' => 'btn btn-success',
];

if (!$progress->complete) {
    $submitattrs['disabled'] = 'disabled';
}

echo html_writer::empty_tag('input', $submitattrs);

if (!$progress->complete) {
    echo html_writer::tag('p', get_string('submitdisabledhint', 'mod_passionfinder'), ['class' => 'form-text text-muted']);
}

echo html_writer::end_tag('form');

echo $OUTPUT->footer();

/**
 * Renders the learner report page.
 *
 * @param core_renderer $output Output renderer.
 * @param stdClass $passionfinder Activity instance.
 * @param stdClass $cm Course module.
 * @param stdClass $attempt Attempt record.
 * @param array $reportresults Results grouped by category id.
 * @return void
 */
function passionfinder_render_report_page($output, stdClass $passionfinder, stdClass $cm, stdClass $attempt, array $reportresults): void {
    global $PAGE;

    echo $output->header();

    $PAGE->requires->js_call_amd('mod_passionfinder/visual_report', 'init');

    echo $output->heading(format_string($passionfinder->name));

    if (!empty($passionfinder->intro)) {
        echo $output->box(format_module_intro('passionfinder', $passionfinder, $cm->id), 'generalbox mod_introbox');
    }

    echo $output->notification(get_string('attemptalreadycompleted', 'mod_passionfinder'), 'success');

    echo $output->heading(get_string('reportheading', 'mod_passionfinder'), 3);
    echo html_writer::tag('p', get_string('reportintro', 'mod_passionfinder'), ['class' => 'alert alert-info']);

    $reportactions = [];

    $reportactions[] = html_writer::link(
        new moodle_url('/mod/passionfinder/pdf.php', ['id' => $cm->id]),
        get_string('downloadpdf', 'mod_passionfinder'),
        ['class' => 'btn btn-primary']
    );

    if (has_capability('mod/passionfinder:viewreports', context_module::instance($cm->id))) {
        $reportactions[] = html_writer::link(
            new moodle_url('/mod/passionfinder/report.php', ['id' => $cm->id]),
            get_string('reports', 'mod_passionfinder'),
            ['class' => 'btn btn-secondary']
        );
    }

    echo html_writer::div(implode(' ', $reportactions), 'passionfinder-report-actions mb-3');

    echo report_builder::render_visual_report($reportresults, (int) $passionfinder->resultdepth, true);

    echo html_writer::tag('p', get_string('reportdisclaimer', 'mod_passionfinder'), ['class' => 'alert alert-secondary']);

    echo html_writer::div(
        html_writer::link(
            new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id, 'set' => 1]),
            get_string('backtoreview', 'mod_passionfinder'),
            ['class' => 'btn btn-secondary']
        ),
        'passionfinder-report-actions'
    );

    echo $output->footer();
}
