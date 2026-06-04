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
 * Teacher report page for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/passionfinder/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('passionfinder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/passionfinder:viewreports', $context);

$PAGE->set_url('/mod/passionfinder/report.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($passionfinder->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($passionfinder->name));
echo $OUTPUT->heading(get_string('reports', 'mod_passionfinder'), 3);

$sql = "SELECT a.id,
               a.userid,
               a.status,
               a.currentset,
               a.timecreated,
               a.timemodified,
               a.completedtime,
               u.firstname,
               u.lastname,
               u.firstnamephonetic,
               u.lastnamephonetic,
               u.middlename,
               u.alternatename,
               u.email,
               COUNT(DISTINCT s.id) AS setcount,
               COUNT(DISTINCT c.id) AS choicecount,
               COUNT(DISTINCT r.id) AS resultcount
          FROM {passionfinder_attempts} a
          JOIN {user} u ON u.id = a.userid
     LEFT JOIN {passionfinder_sets} s ON s.attemptid = a.id
     LEFT JOIN {passionfinder_choices} c ON c.attemptid = a.id
     LEFT JOIN {passionfinder_results} r ON r.attemptid = a.id
         WHERE a.passionfinderid = :passionfinderid
      GROUP BY a.id, a.userid, a.status, a.currentset, a.timecreated, a.timemodified, a.completedtime,
               u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename,
               u.alternatename, u.email
      ORDER BY u.lastname ASC, u.firstname ASC";

$attempts = $DB->get_records_sql($sql, ['passionfinderid' => $passionfinder->id]);

if (empty($attempts)) {
    echo $OUTPUT->notification(get_string('noreportdata', 'mod_passionfinder'), 'info');
    echo $OUTPUT->continue_button(new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id]));
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable passionfinder-report-table';
$table->head = [
    get_string('student', 'mod_passionfinder'),
    get_string('attemptstatus', 'mod_passionfinder'),
    get_string('progress', 'mod_passionfinder'),
    get_string('timesubmitted', 'mod_passionfinder'),
    get_string('actions', 'mod_passionfinder'),
];

foreach ($attempts as $attempt) {
    $studentname = fullname($attempt);
    $profileurl = new moodle_url('/user/view.php', ['id' => $attempt->userid, 'course' => $course->id]);
    $studentlink = html_writer::link($profileurl, s($studentname));

    $iscomplete = passionfinder_report_attempt_is_complete($attempt);

    $statusstring = $iscomplete
        ? get_string('submitted', 'mod_passionfinder')
        : get_string('inprogress', 'mod_passionfinder');

    $progressdata = (object) [
        'answered' => (int) $attempt->choicecount,
        'total' => (int) $attempt->setcount,
    ];

    $progress = get_string('answeredsets', 'mod_passionfinder', $progressdata);

    $timesubmitted = '-';

    if (!empty($attempt->completedtime)) {
        $timesubmitted = userdate($attempt->completedtime);
    } else if ($iscomplete && !empty($attempt->timemodified)) {
        $timesubmitted = userdate($attempt->timemodified);
    }

    $actions = [];

    if ($iscomplete) {
        $pdfurl = new moodle_url('/mod/passionfinder/pdf.php', [
            'id' => $cm->id,
            'attemptid' => $attempt->id,
        ]);

        $actions[] = html_writer::link(
            $pdfurl,
            get_string('downloadpdf', 'mod_passionfinder'),
            ['class' => 'btn btn-primary btn-sm']
        );
    } else {
        $actions[] = html_writer::span(get_string('resultsnotavailable', 'mod_passionfinder'), 'text-muted');
    }

    $table->data[] = [
        $studentlink,
        $statusstring,
        $progress,
        $timesubmitted,
        implode(' ', $actions),
    ];
}

echo html_writer::table($table);

$backurl = new moodle_url('/mod/passionfinder/view.php', ['id' => $cm->id]);
echo html_writer::div(
    html_writer::link($backurl, get_string('viewactivity', 'mod_passionfinder'), ['class' => 'btn btn-secondary']),
    'passionfinder-actions'
);

echo $OUTPUT->footer();

/**
 * Decides whether an attempt should be treated as complete for teacher reporting.
 *
 * Earlier versions could generate results without changing the attempt status to
 * submitted. This keeps those valid completed attempts visible in reports.
 *
 * @param stdClass $attempt Attempt row with counts.
 * @return bool True when the attempt has reportable results.
 */
function passionfinder_report_attempt_is_complete(stdClass $attempt): bool {
    if ($attempt->status === 'submitted') {
        return true;
    }

    if ((int) $attempt->resultcount > 0) {
        return true;
    }

    return (int) $attempt->setcount > 0 && (int) $attempt->choicecount >= (int) $attempt->setcount;
}
