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
 * Browser-based visual report builder for PassionFinder.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_passionfinder\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds visual report HTML using inline SVG.
 *
 * @package    mod_passionfinder
 */
class report_builder {
    /** @var int SVG width. */
    private const SVG_WIDTH = 920;

    /** @var int SVG height. */
    private const SVG_HEIGHT = 310;

    /**
     * Renders a full visual report from grouped scoring results.
     *
     * @param array $reportresults Results grouped by category id.
     * @param int $resultdepth Number of top items to display visually.
     * @param bool $showdatatable Whether to include the detailed data table.
     * @return string HTML report.
     */
    public static function render_visual_report(array $reportresults, int $resultdepth = 5, bool $showdatatable = true): string {
        $html = '';

        foreach ($reportresults as $categoryresults) {
            if (empty($categoryresults)) {
                continue;
            }

            $first = reset($categoryresults);
            $categoryname = !empty($first->categoryname) ? $first->categoryname : $first->categoryid;

            $html .= \html_writer::start_div('generalbox passionfinder-report-category');
            $html .= \html_writer::tag('h4', s($categoryname));
            $html .= \html_writer::tag('p', get_string('visualreportcategoryintro', 'mod_passionfinder'));

            $html .= self::render_category_svg($categoryresults, $resultdepth);

            if ($showdatatable) {
                $html .= self::render_data_table($categoryresults);
            }

            $html .= \html_writer::end_div();
        }

        return $html;
    }

    /**
     * Renders one category as inline SVG hearts.
     *
     * @param array $categoryresults Category result rows.
     * @param int $resultdepth Number of top positive items.
     * @return string SVG HTML.
     */
    public static function render_category_svg(array $categoryresults, int $resultdepth = 5): string {
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

        $positive = array_slice($positive, 0, max(1, $resultdepth));

        usort($negative, static function(\stdClass $a, \stdClass $b): int {
            $ascore = (float) $a->normalisedscore;
            $bscore = (float) $b->normalisedscore;

            if ($ascore === $bscore) {
                return strcmp($a->label, $b->label);
            }

            return $ascore <=> $bscore;
        });

        $negative = array_slice($negative, 0, max(1, $resultdepth));

        if (empty($positive) && empty($negative)) {
            return \html_writer::tag('p', get_string('visualreportnopositive', 'mod_passionfinder'), ['class' => 'alert alert-info']);
        }

        $maxpositivescore = 0.0;
        foreach ($positive as $result) {
            $maxpositivescore = max($maxpositivescore, abs((float) $result->normalisedscore));
        }
        $maxpositivescore = $maxpositivescore > 0 ? $maxpositivescore : 1.0;

        $maxnegativescore = 0.0;
        foreach ($negative as $result) {
            $maxnegativescore = max($maxnegativescore, abs((float) $result->normalisedscore));
        }
        $maxnegativescore = $maxnegativescore > 0 ? $maxnegativescore : 1.0;

        $positivepositions = self::left_positions_for_count(count($positive));
        $negativepositions = self::right_positions_for_count(count($negative));
        $svg = [];

        $svg[] = '<div class="passionfinder-visual-summary" role="img" aria-label="' . s(get_string('visualreportarialabel', 'mod_passionfinder')) . '">';
        $svg[] = '<svg viewBox="0 0 ' . self::SVG_WIDTH . ' ' . self::SVG_HEIGHT . '" xmlns="http://www.w3.org/2000/svg" class="passionfinder-visual-svg">';
        $svg[] = '<rect x="0" y="0" width="' . self::SVG_WIDTH . '" height="' . self::SVG_HEIGHT . '" rx="18" fill="#ffffff"/>';

        if (!empty($positive)) {
            $svg[] = '<text x="235" y="28" text-anchor="middle" fill="#555555" font-size="15" font-weight="700">' . s(get_string('strongestindications', 'mod_passionfinder')) . '</text>';
        }

        if (!empty($negative)) {
            $svg[] = '<text x="690" y="28" text-anchor="middle" fill="#555555" font-size="15" font-weight="700">' . s(get_string('fitsignalstoexamine', 'mod_passionfinder')) . '</text>';
        }

        foreach ($positive as $index => $result) {
            $position = $positivepositions[$index];
            $score = max(0.0, (float) $result->normalisedscore);
            $scale = 0.62 + (($score / $maxpositivescore) * 0.58);
            $width = 145 * $scale;
            $height = 130 * $scale;
            $x = $position['x'] - ($width / 2);
            $y = $position['y'] - ($height / 2);

            $svg[] = self::heart_with_label((float) $x, (float) $y, (float) $width, (float) $height, (string) $result->label, (float) $result->normalisedscore, $index);
        }

        $bubblebaselay = max(0, count($positive)) * 333 + 2200;

        foreach ($negative as $index => $result) {
            $position = $negativepositions[$index];
            $score = abs((float) $result->normalisedscore);
            $scale = 0.70 + (($score / $maxnegativescore) * 0.50);
            $width = 175 * $scale;
            $height = 105 * $scale;
            $x = $position['x'] - ($width / 2);
            $y = $position['y'] - ($height / 2);
            $delay = $bubblebaselay + ($index * 333);

            $svg[] = self::bubble_with_label((float) $x, (float) $y, (float) $width, (float) $height, (string) $result->label, (float) $result->normalisedscore, $delay);
        }

        $svg[] = '</svg>';
        $svg[] = '</div>';

        return implode("\n", $svg);
    }

    /**
     * Renders the detailed scoring table.
     *
     * @param array $categoryresults Category result rows.
     * @return string HTML table.
     */
    public static function render_data_table(array $categoryresults): string {
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable passionfinder-report-table';
        $table->head = [
            get_string('rank', 'mod_passionfinder'),
            get_string('itemlabel', 'mod_passionfinder'),
            get_string('appearances', 'mod_passionfinder'),
            get_string('mostcount', 'mod_passionfinder'),
            get_string('leastcount', 'mod_passionfinder'),
            get_string('netscore', 'mod_passionfinder'),
            get_string('normalisedscore', 'mod_passionfinder'),
        ];

        foreach ($categoryresults as $result) {
            $table->data[] = [
                (int) $result->rank,
                s($result->label),
                (int) $result->appearances,
                (int) $result->mostcount,
                (int) $result->leastcount,
                (int) $result->netscore,
                format_float((float) $result->normalisedscore, 2),
            ];
        }

        return \html_writer::table($table);
    }

    /**
     * Returns practical positions for the visual items.
     *
     * @param int $count Number of items.
     * @return array Position arrays.
     */
    private static function positions_for_count(int $count): array {
        return self::left_positions_for_count($count);
    }

    /**
     * Returns positions for positive hearts on the left side of the visual summary.
     *
     * @param int $count Number of hearts.
     * @return array Position arrays.
     */
    private static function left_positions_for_count(int $count): array {
        $layouts = [
            0 => [],
            1 => [['x' => 235, 'y' => 165]],
            2 => [['x' => 175, 'y' => 155], ['x' => 295, 'y' => 155]],
            3 => [['x' => 235, 'y' => 95], ['x' => 160, 'y' => 210], ['x' => 310, 'y' => 210]],
            4 => [['x' => 170, 'y' => 95], ['x' => 300, 'y' => 95], ['x' => 170, 'y' => 220], ['x' => 300, 'y' => 220]],
            5 => [['x' => 235, 'y' => 70], ['x' => 140, 'y' => 150], ['x' => 330, 'y' => 150], ['x' => 185, 'y' => 235], ['x' => 285, 'y' => 235]],
        ];

        if (isset($layouts[$count])) {
            return $layouts[$count];
        }

        $positions = [];
        $columns = min(3, max(1, $count));
        $rows = (int) ceil($count / $columns);
        $xstart = 105;
        $xwidth = 260;
        $ygap = 235 / max(1, $rows);
        $xgap = $xwidth / max(1, $columns - 1);

        for ($i = 0; $i < $count; $i++) {
            $col = $i % $columns;
            $row = (int) floor($i / $columns);
            $positions[] = [
                'x' => $columns === 1 ? 235 : $xstart + ($col * $xgap),
                'y' => 75 + ($row * $ygap),
            ];
        }

        return $positions;
    }

    /**
     * Returns positions for caution bubbles on the right side of the visual summary.
     *
     * @param int $count Number of bubbles.
     * @return array Position arrays.
     */
    private static function right_positions_for_count(int $count): array {
        $layouts = [
            0 => [],
            1 => [['x' => 690, 'y' => 165]],
            2 => [['x' => 625, 'y' => 165], ['x' => 755, 'y' => 165]],
            3 => [['x' => 690, 'y' => 95], ['x' => 620, 'y' => 215], ['x' => 765, 'y' => 215]],
            4 => [['x' => 625, 'y' => 95], ['x' => 755, 'y' => 95], ['x' => 625, 'y' => 220], ['x' => 755, 'y' => 220]],
            5 => [['x' => 690, 'y' => 70], ['x' => 595, 'y' => 150], ['x' => 785, 'y' => 150], ['x' => 640, 'y' => 235], ['x' => 740, 'y' => 235]],
        ];

        if (isset($layouts[$count])) {
            return $layouts[$count];
        }

        $positions = [];
        $columns = min(3, max(1, $count));
        $rows = (int) ceil($count / $columns);
        $xstart = 570;
        $xwidth = 260;
        $ygap = 235 / max(1, $rows);
        $xgap = $xwidth / max(1, $columns - 1);

        for ($i = 0; $i < $count; $i++) {
            $col = $i % $columns;
            $row = (int) floor($i / $columns);
            $positions[] = [
                'x' => $columns === 1 ? 690 : $xstart + ($col * $xgap),
                'y' => 75 + ($row * $ygap),
            ];
        }

        return $positions;
    }

    /**
     * Renders a single heart with wrapped label text.
     *
     * @param float $x X coordinate.
     * @param float $y Y coordinate.
     * @param float $width Width.
     * @param float $height Height.
     * @param string $label Label text.
     * @param float $score Normalised score.
     * @return string SVG group.
     */
    private static function heart_with_label(float $x, float $y, float $width, float $height, string $label, float $score, int $index = 0): string {
        $cx = $x + ($width / 2);
        $cy = $y + ($height / 2) + 5;
        $font = max(10, min(17, $width / 9.5));
        $lines = self::wrap_label($label, max(8, (int) floor($width / 9)));
        $lineheight = $font * 1.15;
        $starty = $cy - ((count($lines) - 1) * $lineheight / 2);

        $html = [];
        $delay = max(0, $index) * 333;
        $html[] = '<g class="passionfinder-heart" data-score="' . s(format_float($score, 2)) . '" style="animation-delay: ' . $delay . 'ms;">';
        $html[] = '<path d="' . self::heart_path($x, $y, $width, $height) . '" fill="#d92d20" stroke="#a61b13" stroke-width="2"/>';

        $lineindex = 0;
        foreach ($lines as $line) {
            $html[] = '<text x="' . s((string) $cx) . '" y="' . s((string) ($starty + ($lineindex * $lineheight))) . '" text-anchor="middle" dominant-baseline="middle" fill="#f7f7f7" stroke="#5a120e" stroke-width="0.7" paint-order="stroke" font-size="' . s((string) $font) . '" font-weight="700">' . s($line) . '</text>';
            $lineindex++;
        }

        $html[] = '</g>';

        return implode("\n", $html);
    }

    /**
     * Renders a dark caution bubble with wrapped label text.
     *
     * @param float $x X coordinate.
     * @param float $y Y coordinate.
     * @param float $width Width.
     * @param float $height Height.
     * @param string $label Label text.
     * @param float $score Normalised score.
     * @param int $delay Animation delay in milliseconds.
     * @return string SVG group.
     */
    private static function bubble_with_label(float $x, float $y, float $width, float $height, string $label, float $score, int $delay = 0): string {
        $cx = $x + ($width / 2);
        $cy = $y + ($height / 2);
        $font = max(11, min(18, $width / 11.0));
        $lines = self::wrap_label($label, max(9, (int) floor($width / 12)));
        $lineheight = $font * 1.15;
        $starty = $cy - ((count($lines) - 1) * $lineheight / 2);

        $html = [];
        $html[] = '<g class="passionfinder-heart passionfinder-caution-bubble" data-score="' . s(format_float($score, 2)) . '" style="animation-delay: ' . max(0, $delay) . 'ms;">';
        $html[] = '<ellipse cx="' . s((string) $cx) . '" cy="' . s((string) $cy) . '" rx="' . s((string) ($width / 2)) . '" ry="' . s((string) ($height / 2)) . '" fill="#2b2b2b" stroke="#003049" stroke-width="3"/>';

        $lineindex = 0;
        foreach ($lines as $line) {
            $html[] = '<text x="' . s((string) $cx) . '" y="' . s((string) ($starty + ($lineindex * $lineheight))) . '" text-anchor="middle" dominant-baseline="middle" fill="#ffffff" font-size="' . s((string) $font) . '" font-weight="700">' . s($line) . '</text>';
            $lineindex++;
        }

        $questionx = $cx + ($width * 0.34);
        $questiony = $cy + ($height * 0.25);
        $html[] = '<text x="' . s((string) $questionx) . '" y="' . s((string) $questiony) . '" text-anchor="middle" dominant-baseline="middle" fill="#ff477e" font-size="' . s((string) max(24, min(42, $width / 5))) . '" font-weight="900">?</text>';
        $html[] = '</g>';

        return implode("\n", $html);
    }

    /**
     * Returns an SVG path for a heart shape in the given box.
     *
     * @param float $x X coordinate.
     * @param float $y Y coordinate.
     * @param float $width Width.
     * @param float $height Height.
     * @return string Path data.
     */
    private static function heart_path(float $x, float $y, float $width, float $height): string {
        $p = static function(float $px, float $py) use ($x, $y, $width, $height): string {
            return format_float($x + ($px * $width), 2) . ' ' . format_float($y + ($py * $height), 2);
        };

        return 'M ' . $p(0.50, 0.92) .
            ' C ' . $p(0.10, 0.62) . ', ' . $p(0.00, 0.38) . ', ' . $p(0.18, 0.18) .
            ' C ' . $p(0.34, 0.00) . ', ' . $p(0.50, 0.12) . ', ' . $p(0.50, 0.30) .
            ' C ' . $p(0.50, 0.12) . ', ' . $p(0.66, 0.00) . ', ' . $p(0.82, 0.18) .
            ' C ' . $p(1.00, 0.38) . ', ' . $p(0.90, 0.62) . ', ' . $p(0.50, 0.92) .
            ' Z';
    }

    /**
     * Wraps a short label into SVG text lines.
     *
     * @param string $label Label.
     * @param int $maxlength Approximate max characters per line.
     * @return array Wrapped lines.
     */
    private static function wrap_label(string $label, int $maxlength): array {
        $words = preg_split('/\s+/', trim($label));
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;

            if (\core_text::strlen($candidate) <= $maxlength || $current === '') {
                $current = $candidate;
                continue;
            }

            $lines[] = $current;
            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }
}
