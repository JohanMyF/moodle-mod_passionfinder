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
 * Choice-set generator for PassionFinder best-worst activities.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_passionfinder\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Generates balanced best-worst choice sets from a parsed PassionFinder instrument.
 *
 * The goal is practical educational usefulness rather than full statistical MaxDiff
 * design optimisation. The generator keeps the process deterministic per user and
 * attempt seed, spreads item appearances as evenly as possible, and freezes the
 * generated sets for later storage in passionfinder_sets.
 *
 * @package    mod_passionfinder
 */
class choice_set_generator {
    /** @var int Maximum attempts to improve a candidate set. */
    private const CANDIDATE_ATTEMPTS = 30;

    /**
     * Generates all choice sets for all categories in an instrument.
     *
     * @param \stdClass $instrument Normalised instrument from instrument_parser.
     * @param int $seed Stable seed, normally based on activity id and user id.
     * @return array Generated set objects.
     */
    public static function generate(\stdClass $instrument, int $seed = 1): array {
        $sets = [];
        $globalsetnumber = 1;

        foreach ($instrument->categories as $categoryindex => $category) {
            $categorysets = self::generate_for_category(
                $category,
                (int) $instrument->settings->itemsperround,
                (int) $instrument->settings->roundspercategory,
                $seed + ($categoryindex * 997)
            );

            foreach ($categorysets as $categoryset) {
                $categoryset->setnumber = $globalsetnumber;
                $sets[] = $categoryset;
                $globalsetnumber++;
            }
        }

        return $sets;
    }

    /**
     * Generates choice sets for a single category.
     *
     * @param \stdClass $category Category object.
     * @param int $itemsperround Items shown in each set.
     * @param int $roundspercategory Number of sets to generate.
     * @param int $seed Stable seed.
     * @return array Generated set objects.
     */
    public static function generate_for_category(\stdClass $category, int $itemsperround, int $roundspercategory, int $seed = 1): array {
        $items = array_values($category->items);
        $itemcount = count($items);

        if ($itemcount === 0 || $roundspercategory <= 0) {
            return [];
        }

        $itemsperround = max(1, min($itemsperround, $itemcount));
        $roundspercategory = max(1, $roundspercategory);

        $appearances = [];
        $pairings = [];

        foreach ($items as $item) {
            $appearances[$item->id] = 0;
            $pairings[$item->id] = [];
        }

        $sets = [];
        $rngstate = self::normalise_seed($seed);

        for ($round = 1; $round <= $roundspercategory; $round++) {
            $candidate = self::select_best_candidate($items, $itemsperround, $appearances, $pairings, $rngstate);

            foreach ($candidate as $item) {
                $appearances[$item->id]++;
            }

            self::record_pairings($candidate, $pairings);

            $set = new \stdClass();
            $set->categoryid = $category->id;
            $set->categoryname = $category->name;
            $set->prompt = $category->prompt;
            $set->mostlabel = $category->mostlabel;
            $set->leastlabel = $category->leastlabel;
            $set->categorysetnumber = $round;
            $set->items = array_values($candidate);

            $sets[] = $set;
        }

        return $sets;
    }

    /**
     * Returns a compact JSON-ready payload for database storage.
     *
     * @param \stdClass $set Generated set object.
     * @return string JSON representation of the frozen set.
     */
    public static function set_to_json(\stdClass $set): string {
        return json_encode($set, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Selects the best candidate set by scoring several candidate shuffles.
     *
     * @param array $items Available item objects.
     * @param int $itemsperround Number of items to select.
     * @param array $appearances Current appearance counts.
     * @param array $pairings Current pair counts.
     * @param int $rngstate RNG state, updated by reference.
     * @return array Selected item objects.
     */
    private static function select_best_candidate(array $items, int $itemsperround, array $appearances, array $pairings, int &$rngstate): array {
        $bestcandidate = [];
        $bestscore = null;

        for ($attempt = 0; $attempt < self::CANDIDATE_ATTEMPTS; $attempt++) {
            $shuffled = self::deterministic_shuffle($items, $rngstate);

            usort($shuffled, static function(\stdClass $a, \stdClass $b) use ($appearances): int {
                $appearancecompare = ($appearances[$a->id] ?? 0) <=> ($appearances[$b->id] ?? 0);

                if ($appearancecompare !== 0) {
                    return $appearancecompare;
                }

                return strcmp($a->id, $b->id);
            });

            $candidate = array_slice($shuffled, 0, $itemsperround);
            $score = self::score_candidate($candidate, $appearances, $pairings);

            if ($bestscore === null || $score < $bestscore) {
                $bestscore = $score;
                $bestcandidate = $candidate;
            }
        }

        return self::deterministic_shuffle($bestcandidate, $rngstate);
    }

    /**
     * Scores a candidate set. Lower is better.
     *
     * @param array $candidate Candidate item objects.
     * @param array $appearances Current appearance counts.
     * @param array $pairings Current pair counts.
     * @return int Candidate score.
     */
    private static function score_candidate(array $candidate, array $appearances, array $pairings): int {
        $score = 0;

        foreach ($candidate as $item) {
            $score += ($appearances[$item->id] ?? 0) * 100;
        }

        $count = count($candidate);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $firstid = $candidate[$i]->id;
                $secondid = $candidate[$j]->id;
                $score += self::pair_count($firstid, $secondid, $pairings) * 10;
            }
        }

        return $score;
    }

    /**
     * Records pairings after a set has been selected.
     *
     * @param array $candidate Selected item objects.
     * @param array $pairings Pairing counts, updated by reference.
     * @return void
     */
    private static function record_pairings(array $candidate, array &$pairings): void {
        $count = count($candidate);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $firstid = $candidate[$i]->id;
                $secondid = $candidate[$j]->id;

                if (!isset($pairings[$firstid][$secondid])) {
                    $pairings[$firstid][$secondid] = 0;
                }

                if (!isset($pairings[$secondid][$firstid])) {
                    $pairings[$secondid][$firstid] = 0;
                }

                $pairings[$firstid][$secondid]++;
                $pairings[$secondid][$firstid]++;
            }
        }
    }

    /**
     * Returns the current pairing count for two items.
     *
     * @param string $firstid First item id.
     * @param string $secondid Second item id.
     * @param array $pairings Pairing counts.
     * @return int Pairing count.
     */
    private static function pair_count(string $firstid, string $secondid, array $pairings): int {
        return (int) ($pairings[$firstid][$secondid] ?? 0);
    }

    /**
     * Deterministically shuffles an array using a small linear congruential generator.
     *
     * @param array $items Items to shuffle.
     * @param int $state RNG state, updated by reference.
     * @return array Shuffled items.
     */
    private static function deterministic_shuffle(array $items, int &$state): array {
        $items = array_values($items);

        for ($i = count($items) - 1; $i > 0; $i--) {
            $state = self::next_random_state($state);
            $j = $state % ($i + 1);

            $tmp = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $tmp;
        }

        return $items;
    }

    /**
     * Returns the next deterministic random state.
     *
     * @param int $state Current RNG state.
     * @return int Next RNG state.
     */
    private static function next_random_state(int $state): int {
        return (1103515245 * $state + 12345) & 0x7fffffff;
    }

    /**
     * Normalises a seed to a positive integer.
     *
     * @param int $seed Raw seed.
     * @return int Positive seed.
     */
    private static function normalise_seed(int $seed): int {
        $seed = abs($seed);

        if ($seed === 0) {
            return 1;
        }

        return $seed;
    }
}
