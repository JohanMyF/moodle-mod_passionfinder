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
 * The generator aims to minimise respondent burden while spreading item exposure and
 * pair co-occurrence as evenly as practical. For the common 10-item / 5-per-screen
 * configuration it uses exact covering designs at 6, 10, 14 and 18 screens. These
 * guarantee that every item pair appears together at least 1, 2, 3 or 4 times
 * respectively. Other configurations use a deterministic balanced heuristic.
 *
 * Generated sets remain deterministic for a given seed so that an attempt can be
 * reproduced reliably before the frozen sets are stored in passionfinder_sets.
 *
 * @package    mod_passionfinder
 */
class choice_set_generator {
    /** @var int Number of candidate sets sampled by the fallback heuristic. */
    private const CANDIDATE_ATTEMPTS = 120;

    /**
     * Exact covering templates for 10 items shown 5 at a time.
     *
     * Templates are indexed by the number of screens. Item numbers are positions
     * 0..9 after a deterministic seed-based permutation. The designs were solved as
     * pair-covering/multicover designs and have perfectly balanced item exposure.
     *
     * @var array<int, array<int, array<int>>>
     */
    private const TEN_BY_FIVE_TEMPLATES = [
        6 => [
            [0, 1, 2, 4, 6],
            [0, 2, 3, 5, 8],
            [0, 4, 5, 7, 9],
            [1, 3, 4, 7, 8],
            [1, 3, 5, 6, 9],
            [2, 6, 7, 8, 9],
        ],
        10 => [
            [0, 1, 2, 6, 9],
            [0, 1, 3, 5, 8],
            [0, 1, 4, 5, 9],
            [0, 2, 7, 8, 9],
            [0, 3, 4, 6, 7],
            [1, 2, 3, 4, 7],
            [1, 5, 6, 7, 8],
            [2, 3, 5, 7, 9],
            [2, 4, 5, 6, 8],
            [3, 4, 6, 8, 9],
        ],
        14 => [
            [0, 1, 2, 6, 7],
            [0, 1, 4, 5, 8],
            [0, 1, 5, 6, 9],
            [0, 2, 3, 4, 6],
            [0, 2, 7, 8, 9],
            [0, 3, 4, 5, 7],
            [0, 3, 7, 8, 9],
            [1, 2, 3, 4, 9],
            [1, 2, 3, 5, 7],
            [1, 3, 6, 8, 9],
            [1, 4, 6, 7, 8],
            [2, 3, 5, 6, 8],
            [2, 4, 5, 8, 9],
            [4, 5, 6, 7, 9],
        ],
        18 => [
            [0, 1, 2, 3, 5],
            [0, 1, 2, 4, 6],
            [0, 1, 5, 7, 9],
            [0, 1, 6, 7, 8],
            [0, 2, 3, 6, 9],
            [0, 2, 4, 7, 8],
            [0, 3, 4, 8, 9],
            [0, 3, 5, 6, 8],
            [0, 4, 5, 7, 9],
            [1, 2, 3, 4, 7],
            [1, 2, 5, 8, 9],
            [1, 3, 4, 5, 8],
            [1, 3, 6, 7, 9],
            [1, 4, 6, 8, 9],
            [2, 3, 7, 8, 9],
            [2, 4, 5, 6, 9],
            [2, 5, 6, 7, 8],
            [3, 4, 5, 6, 7],
        ],
    ];

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
    public static function generate_for_category(\stdClass $category, int $itemsperround,
            int $roundspercategory, int $seed = 1): array {
        $items = array_values($category->items);
        $itemcount = count($items);

        if ($itemcount === 0 || $roundspercategory <= 0) {
            return [];
        }

        $itemsperround = max(1, min($itemsperround, $itemcount));
        $roundspercategory = max(1, $roundspercategory);
        $rngstate = self::normalise_seed($seed);

        $appearances = [];
        $pairings = [];
        foreach ($items as $item) {
            $appearances[$item->id] = 0;
            $pairings[$item->id] = [];
        }

        $rawsets = [];

        // Use an exact pair-covering design for the common 10 x 5 configuration.
        if ($itemcount === 10 && $itemsperround === 5 && $roundspercategory >= 6) {
            $rawsets = self::generate_ten_by_five_sets(
                $items,
                $roundspercategory,
                $appearances,
                $pairings,
                $rngstate
            );
        } else {
            for ($round = 1; $round <= $roundspercategory; $round++) {
                $candidate = self::select_best_candidate($items, $itemsperround, $appearances, $pairings, $rngstate);
                self::record_candidate($candidate, $appearances, $pairings);
                $rawsets[] = $candidate;
            }
        }

        $sets = [];
        foreach ($rawsets as $index => $candidate) {
            $set = new \stdClass();
            $set->categoryid = $category->id;
            $set->categoryname = $category->name;
            $set->prompt = $category->prompt;
            $set->mostlabel = $category->mostlabel;
            $set->leastlabel = $category->leastlabel;
            $set->categorysetnumber = $index + 1;
            $set->items = array_values(self::deterministic_shuffle($candidate, $rngstate));
            $sets[] = $set;
        }

        return $sets;
    }

    /**
     * Builds the common 10-item / 5-per-screen design.
     *
     * The largest exact template not exceeding the requested number of screens is
     * used first. Any extra screens are then added by the balanced fallback
     * heuristic. This means 6, 10, 14 and 18 screens provide guaranteed minimum
     * pair co-occurrence of 1, 2, 3 and 4 respectively, while intermediate screen
     * counts preserve the guarantee of the preceding exact template.
     *
     * @param array $items Ten item objects.
     * @param int $roundspercategory Requested screens.
     * @param array $appearances Appearance counts, updated by reference.
     * @param array $pairings Pair counts, updated by reference.
     * @param int $rngstate RNG state, updated by reference.
     * @return array Array of candidate sets.
     */
    private static function generate_ten_by_five_sets(array $items, int $roundspercategory,
            array &$appearances, array &$pairings, int &$rngstate): array {
        $permuteditems = self::deterministic_shuffle($items, $rngstate);

        $templatesizes = array_keys(self::TEN_BY_FIVE_TEMPLATES);
        rsort($templatesizes, SORT_NUMERIC);
        $basesize = 6;
        foreach ($templatesizes as $templatesize) {
            if ($templatesize <= $roundspercategory) {
                $basesize = $templatesize;
                break;
            }
        }

        $sets = [];
        foreach (self::TEN_BY_FIVE_TEMPLATES[$basesize] as $positions) {
            $candidate = [];
            foreach ($positions as $position) {
                $candidate[] = $permuteditems[$position];
            }
            self::record_candidate($candidate, $appearances, $pairings);
            $sets[] = $candidate;
        }

        while (count($sets) < $roundspercategory) {
            $candidate = self::select_best_candidate($items, 5, $appearances, $pairings, $rngstate);
            self::record_candidate($candidate, $appearances, $pairings);
            $sets[] = $candidate;
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
     * Selects a balanced candidate set using deterministic random sampling.
     *
     * Candidate generation deliberately keeps the random shuffle as the tie-breaker
     * when items have equal appearance counts. The previous implementation sorted
     * equal-count items by id, which unintentionally collapsed different shuffles
     * into the same candidate and prevented the pairing score from working properly.
     *
     * @param array $items Available item objects.
     * @param int $itemsperround Number of items to select.
     * @param array $appearances Current appearance counts.
     * @param array $pairings Current pair counts.
     * @param int $rngstate RNG state, updated by reference.
     * @return array Selected item objects.
     */
    private static function select_best_candidate(array $items, int $itemsperround,
            array $appearances, array $pairings, int &$rngstate): array {
        $bestcandidate = [];
        $bestscore = null;

        for ($attempt = 0; $attempt < self::CANDIDATE_ATTEMPTS; $attempt++) {
            $shuffled = self::deterministic_shuffle($items, $rngstate);
            $randomrank = [];
            foreach ($shuffled as $rank => $item) {
                $randomrank[$item->id] = $rank;
            }

            usort($shuffled, static function(\stdClass $a, \stdClass $b) use ($appearances, $randomrank): int {
                $appearancecompare = ($appearances[$a->id] ?? 0) <=> ($appearances[$b->id] ?? 0);
                if ($appearancecompare !== 0) {
                    return $appearancecompare;
                }
                return ($randomrank[$a->id] ?? 0) <=> ($randomrank[$b->id] ?? 0);
            });

            $candidate = array_slice($shuffled, 0, $itemsperround);
            $score = self::score_candidate($candidate, $appearances, $pairings);

            if ($bestscore === null || self::score_is_better($score, $bestscore)) {
                $bestscore = $score;
                $bestcandidate = $candidate;
            }
        }

        return $bestcandidate;
    }

    /**
     * Scores a candidate lexicographically. Lower is better.
     *
     * Pair repetition is considered before appearance variance because the candidate
     * pool has already been restricted toward low-appearance items. This encourages
     * new pair coverage without allowing a small subset of items to dominate.
     *
     * @param array $candidate Candidate item objects.
     * @param array $appearances Current appearance counts.
     * @param array $pairings Current pair counts.
     * @return array<int, int> Lexicographic score tuple.
     */
    private static function score_candidate(array $candidate, array $appearances, array $pairings): array {
        $pairrepetition = 0;
        $pairmaximum = 0;
        $count = count($candidate);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $firstid = $candidate[$i]->id;
                $secondid = $candidate[$j]->id;
                $paircount = self::pair_count($firstid, $secondid, $pairings);
                $pairrepetition += $paircount;
                $pairmaximum = max($pairmaximum, $paircount);
            }
        }

        $projected = $appearances;
        foreach ($candidate as $item) {
            $projected[$item->id] = ($projected[$item->id] ?? 0) + 1;
        }

        $appearancevalues = array_values($projected);
        $appearancespread = max($appearancevalues) - min($appearancevalues);
        $appearancesquares = 0;
        foreach ($appearancevalues as $value) {
            $appearancesquares += $value * $value;
        }

        return [$pairmaximum, $pairrepetition, $appearancespread, $appearancesquares];
    }

    /**
     * Compares two lexicographic score tuples.
     *
     * @param array $candidate Candidate score.
     * @param array $current Current best score.
     * @return bool True when candidate is better.
     */
    private static function score_is_better(array $candidate, array $current): bool {
        $count = min(count($candidate), count($current));
        for ($i = 0; $i < $count; $i++) {
            if ($candidate[$i] < $current[$i]) {
                return true;
            }
            if ($candidate[$i] > $current[$i]) {
                return false;
            }
        }
        return false;
    }

    /**
     * Records appearances and pairings for a selected candidate.
     *
     * @param array $candidate Selected item objects.
     * @param array $appearances Appearance counts, updated by reference.
     * @param array $pairings Pair counts, updated by reference.
     * @return void
     */
    private static function record_candidate(array $candidate, array &$appearances, array &$pairings): void {
        foreach ($candidate as $item) {
            $appearances[$item->id] = ($appearances[$item->id] ?? 0) + 1;
        }
        self::record_pairings($candidate, $pairings);
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
        return $seed === 0 ? 1 : $seed;
    }
}
