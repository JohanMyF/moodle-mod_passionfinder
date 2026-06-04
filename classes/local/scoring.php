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
 * Scoring engine for PassionFinder attempts.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_passionfinder\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Calculates simple transparent best-worst scores.
 *
 * Score model:
 * - Item chosen as Most: +1
 * - Item chosen as Least: -1
 * - Item shown but not chosen: 0
 *
 * Normalised score:
 * - netscore / appearances
 *
 * @package    mod_passionfinder
 */
class scoring {
    /**
     * Calculates and stores result rows for an attempt.
     *
     * Existing stored results for the attempt are replaced.
     *
     * @param int $attemptid Attempt id.
     * @return array Result objects grouped by category id.
     */
    public static function calculate_and_store(int $attemptid): array {
        global $DB;

        $results = self::calculate($attemptid);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('passionfinder_results', ['attemptid' => $attemptid]);

        $now = time();

        foreach ($results as $categoryid => $categoryresults) {
            foreach ($categoryresults as $result) {
                $record = new \stdClass();
                $record->attemptid = $attemptid;
                $record->categoryid = $categoryid;
                $record->itemid = $result->itemid;
                $record->appearances = $result->appearances;
                $record->mostcount = $result->mostcount;
                $record->leastcount = $result->leastcount;
                $record->netscore = $result->netscore;
                $record->normalisedscore = $result->normalisedscore;
                $record->rank = $result->rank;
                $record->timecreated = $now;

                $DB->insert_record('passionfinder_results', $record);
            }
        }

        $transaction->allow_commit();

        return $results;
    }

    /**
     * Calculates result rows for an attempt without storing them.
     *
     * @param int $attemptid Attempt id.
     * @return array Result objects grouped by category id.
     */
    public static function calculate(int $attemptid): array {
        $sets = attempt_manager::get_attempt_sets($attemptid);
        $choices = attempt_manager::get_choices_by_set($attemptid);

        $scores = [];
        $categorynames = [];
        $itemlabels = [];

        foreach ($sets as $setrecord) {
            if (empty($setrecord->setdata) || empty($setrecord->setdata->items) || !is_array($setrecord->setdata->items)) {
                continue;
            }

            $categoryid = (string) $setrecord->categoryid;
            $categorynames[$categoryid] = (string) ($setrecord->setdata->categoryname ?? $categoryid);

            if (!isset($scores[$categoryid])) {
                $scores[$categoryid] = [];
            }

            foreach ($setrecord->setdata->items as $item) {
                if (empty($item->id)) {
                    continue;
                }

                $itemid = (string) $item->id;
                $itemlabels[$categoryid][$itemid] = (string) ($item->label ?? $itemid);

                if (!isset($scores[$categoryid][$itemid])) {
                    $scores[$categoryid][$itemid] = self::new_score_row($categoryid, $categorynames[$categoryid], $itemid, $itemlabels[$categoryid][$itemid]);
                }

                $scores[$categoryid][$itemid]->appearances++;
            }

            $choice = $choices[(int) $setrecord->id] ?? null;

            if (!$choice) {
                continue;
            }

            if (!empty($choice->mostitemid) && isset($scores[$categoryid][$choice->mostitemid])) {
                $scores[$categoryid][$choice->mostitemid]->mostcount++;
            }

            if (!empty($choice->leastitemid) && isset($scores[$categoryid][$choice->leastitemid])) {
                $scores[$categoryid][$choice->leastitemid]->leastcount++;
            }
        }

        foreach ($scores as $categoryid => $categoryscores) {
            foreach ($categoryscores as $itemid => $row) {
                $row->netscore = $row->mostcount - $row->leastcount;
                $row->normalisedscore = $row->appearances > 0 ? $row->netscore / $row->appearances : 0;
            }

            uasort($categoryscores, static function(\stdClass $a, \stdClass $b): int {
                if ($a->normalisedscore === $b->normalisedscore) {
                    if ($a->netscore === $b->netscore) {
                        return strcmp($a->label, $b->label);
                    }

                    return $b->netscore <=> $a->netscore;
                }

                return $b->normalisedscore <=> $a->normalisedscore;
            });

            $rank = 1;
            foreach ($categoryscores as $row) {
                $row->rank = $rank;
                $rank++;
            }

            $scores[$categoryid] = array_values($categoryscores);
        }

        return $scores;
    }

    /**
     * Returns stored result rows grouped by category id.
     *
     * @param int $attemptid Attempt id.
     * @return array Stored result records grouped by category id.
     */
    public static function get_stored_results(int $attemptid): array {
        global $DB;

        $records = $DB->get_records('passionfinder_results', ['attemptid' => $attemptid], 'categoryid ASC, rank ASC, id ASC');
        $grouped = [];

        foreach ($records as $record) {
            $grouped[$record->categoryid][] = $record;
        }

        return $grouped;
    }

    /**
     * Creates a new score row object.
     *
     * @param string $categoryid Category id.
     * @param string $categoryname Category name.
     * @param string $itemid Item id.
     * @param string $label Item label.
     * @return \stdClass Score row.
     */
    private static function new_score_row(string $categoryid, string $categoryname, string $itemid, string $label): \stdClass {
        $row = new \stdClass();
        $row->categoryid = $categoryid;
        $row->categoryname = $categoryname;
        $row->itemid = $itemid;
        $row->label = $label;
        $row->appearances = 0;
        $row->mostcount = 0;
        $row->leastcount = 0;
        $row->netscore = 0;
        $row->normalisedscore = 0.0;
        $row->rank = 0;

        return $row;
    }
}
