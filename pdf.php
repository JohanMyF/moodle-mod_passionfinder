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
 * PDF results download page for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/passionfinder/lib.php');
require_once($CFG->libdir . '/pdflib.php');

$id = required_param('id', PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('passionfinder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$passionfinder = $DB->get_record('passionfinder', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/passionfinder:view', $context);

$viewingotheruser = false;

if ($attemptid > 0) {
    require_capability('mod/passionfinder:viewreports', $context);

    $attempt = $DB->get_record('passionfinder_attempts', [
        'id' => $attemptid,
        'passionfinderid' => $passionfinder->id,
    ], '*', MUST_EXIST);

    $targetuserid = (int) $attempt->userid;
    $viewingotheruser = ($targetuserid !== (int) $USER->id);
} else {
    $targetuserid = (int) $USER->id;
    $attempt = $DB->get_record(
        'passionfinder_attempts',
        ['passionfinderid' => $passionfinder->id, 'userid' => $targetuserid],
        '*',
        MUST_EXIST
    );
}

$targetuser = $DB->get_record('user', ['id' => $targetuserid, 'deleted' => 0], '*', MUST_EXIST);

if ($viewingotheruser && !is_enrolled($context, $targetuser, '', true)) {
    throw new moodle_exception('invaliduser', 'error');
}

$results = $DB->get_records('passionfinder_results', ['attemptid' => $attempt->id], 'categoryid ASC, rank ASC, id ASC');

if (empty($results)) {
    throw new moodle_exception('resultsnotavailable', 'mod_passionfinder');
}

$instrument = passionfinder_pdf_decode_instrument($passionfinder);
$labels = passionfinder_pdf_label_maps($instrument);

$grouped = [];

foreach ($results as $result) {
    $categoryid = (string) $result->categoryid;
    $itemid = (string) $result->itemid;
    $result->categoryname = $labels['categories'][$categoryid] ?? $categoryid;
    $result->itemlabel = $labels['items'][$itemid] ?? $itemid;
    $grouped[$categoryid][] = $result;
}

$submittedtext = '-';
if (!empty($attempt->completedtime)) {
    $submittedtext = userdate($attempt->completedtime);
}

$filenamebase = clean_param(format_string($passionfinder->name) . '-' . fullname($targetuser), PARAM_FILE);
if ($filenamebase === '') {
    $filenamebase = 'passionfinder-results';
}
$filename = $filenamebase . '.pdf';

$pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8');

$pdf->SetCreator('Moodle');
$pdf->SetAuthor(fullname($targetuser));
$pdf->SetTitle(format_string($passionfinder->name) . ' - ' . get_string('yourresults', 'mod_passionfinder'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$html = '';

$html .= html_writer::tag('h1', s(format_string($passionfinder->name)));
$html .= html_writer::tag('h2', s(get_string('passionfinderresultreport', 'mod_passionfinder')));

$html .= '<table cellpadding="4" cellspacing="0" border="0">';
$html .= '<tr><td width="30%"><strong>' . s(get_string('student', 'mod_passionfinder')) . '</strong></td><td width="70%">' .
    s(fullname($targetuser)) . '</td></tr>';
$html .= '<tr><td width="30%"><strong>' . s(get_string('course')) . '</strong></td><td width="70%">' .
    s(format_string($course->fullname)) . '</td></tr>';
$html .= '<tr><td width="30%"><strong>' . s(get_string('timesubmitted', 'mod_passionfinder')) . '</strong></td><td width="70%">' .
    s($submittedtext) . '</td></tr>';
$html .= '</table>';

$html .= html_writer::tag('h3', s(get_string('howtoreadresults', 'mod_passionfinder')));
$html .= html_writer::tag('p', s(get_string('pdfreportintro', 'mod_passionfinder')));

foreach ($grouped as $categoryid => $categoryresults) {
    $categoryname = $labels['categories'][$categoryid] ?? $categoryid;

    $html .= html_writer::tag('h3', s(format_string($categoryname)));

    $positive = [];
    $negative = [];

    foreach ($categoryresults as $result) {
        $score = (float) $result->normalisedscore;

        if ($score > 0) {
            $positive[] = $result;
        } else if ($score < 0) {
            $negative[] = $result;
        }
    }

    usort($negative, static function(stdClass $a, stdClass $b): int {
        $ascore = (float) $a->normalisedscore;
        $bscore = (float) $b->normalisedscore;

        if ($ascore === $bscore) {
            return strcmp($a->itemlabel, $b->itemlabel);
        }

        return $ascore <=> $bscore;
    });

    $positive = array_slice($positive, 0, 5);
    $negative = array_slice($negative, 0, 5);

    $html .= '<table cellpadding="5" cellspacing="0" border="1" width="100%">';
    $html .= '<thead><tr style="font-weight:bold;background-color:#eeeeee;">';
    $html .= '<th width="50%">' . s(get_string('strongestindications', 'mod_passionfinder')) . '</th>';
    $html .= '<th width="50%">' . s(get_string('fitsignalstoexamine', 'mod_passionfinder')) . '</th>';
    $html .= '</tr></thead><tbody>';

    $rows = max(count($positive), count($negative));

    for ($i = 0; $i < $rows; $i++) {
        $positivehtml = '&nbsp;';
        $negativehtml = '&nbsp;';

        if (!empty($positive[$i])) {
            $positivehtml = '<strong>' . s($positive[$i]->itemlabel) . '</strong><br><span style="font-size:9pt;">' .
                s(format_float((float) $positive[$i]->normalisedscore, 2)) . '</span>';
        }

        if (!empty($negative[$i])) {
            $negativehtml = '<strong>' . s($negative[$i]->itemlabel) . '</strong><br><span style="font-size:9pt;">' .
                s(format_float((float) $negative[$i]->normalisedscore, 2)) . '</span>';
        }

        $html .= '<tr>';
        $html .= '<td width="50%">' . $positivehtml . '</td>';
        $html .= '<td width="50%">' . $negativehtml . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    $html .= html_writer::tag('h4', s(get_string('datatable', 'mod_passionfinder')));
    $html .= '<table cellpadding="5" cellspacing="0" border="1" width="100%">';
    $html .= '<thead><tr style="font-weight:bold;background-color:#eeeeee;">';
    $html .= '<th width="8%">' . s(get_string('rank', 'mod_passionfinder')) . '</th>';
    $html .= '<th width="42%">' . s(get_string('itemlabel', 'mod_passionfinder')) . '</th>';
    $html .= '<th width="13%">' . s(get_string('appearances', 'mod_passionfinder')) . '</th>';
    $html .= '<th width="10%">' . s(get_string('mostcount', 'mod_passionfinder')) . '</th>';
    $html .= '<th width="10%">' . s(get_string('leastcount', 'mod_passionfinder')) . '</th>';
    $html .= '<th width="17%">' . s(get_string('normalisedscore', 'mod_passionfinder')) . '</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($categoryresults as $result) {
        $html .= '<tr>';
        $html .= '<td width="8%">' . s((string) $result->rank) . '</td>';
        $html .= '<td width="42%">' . s($result->itemlabel) . '</td>';
        $html .= '<td width="13%">' . s((string) $result->appearances) . '</td>';
        $html .= '<td width="10%">' . s((string) $result->mostcount) . '</td>';
        $html .= '<td width="10%">' . s((string) $result->leastcount) . '</td>';
        $html .= '<td width="17%">' . s(format_float((float) $result->normalisedscore, 2)) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
}

$html .= html_writer::tag('h3', s(get_string('importantnote', 'mod_passionfinder')));
$html .= html_writer::tag('p', s(get_string('resultdisclaimer', 'mod_passionfinder')));

$pdf->writeHTML($html);
$pdf->Output($filename, 'D');
exit;

/**
 * Decodes the stored instrument JSON.
 *
 * @param stdClass $passionfinder Activity instance.
 * @return stdClass Instrument object.
 */
function passionfinder_pdf_decode_instrument(stdClass $passionfinder): stdClass {
    $instrument = json_decode($passionfinder->instrumentjson ?? '');

    if (json_last_error() === JSON_ERROR_NONE && is_object($instrument)) {
        return $instrument;
    }

    return new stdClass();
}

/**
 * Builds category and item label maps from the instrument JSON.
 *
 * @param stdClass $instrument Instrument object.
 * @return array Label maps.
 */
function passionfinder_pdf_label_maps(stdClass $instrument): array {
    $maps = [
        'categories' => [],
        'items' => [],
    ];

    if (empty($instrument->categories) || !is_array($instrument->categories)) {
        return $maps;
    }

    foreach ($instrument->categories as $category) {
        if (!is_object($category) || empty($category->id)) {
            continue;
        }

        $categoryid = (string) $category->id;
        $maps['categories'][$categoryid] = !empty($category->name) ? (string) $category->name : $categoryid;

        if (empty($category->items) || !is_array($category->items)) {
            continue;
        }

        foreach ($category->items as $item) {
            if (!is_object($item) || empty($item->id)) {
                continue;
            }

            $itemid = (string) $item->id;
            $maps['items'][$itemid] = !empty($item->label) ? (string) $item->label : $itemid;
        }
    }

    return $maps;
}
