<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Tests for the PassionFinder choice-set generator.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_passionfinder;

use mod_passionfinder\local\choice_set_generator;

/**
 * Choice-set generator tests.
 */
final class choice_set_generator_test extends \advanced_testcase {
    /**
     * Creates a simple category with the requested number of items.
     *
     * @param int $count Item count.
     * @return \stdClass
     */
    private function make_category(int $count): \stdClass {
        $category = new \stdClass();
        $category->id = 'test';
        $category->name = 'Test';
        $category->prompt = 'Choose';
        $category->mostlabel = 'Most';
        $category->leastlabel = 'Least';
        $category->items = [];

        for ($i = 0; $i < $count; $i++) {
            $item = new \stdClass();
            $item->id = 'item_' . $i;
            $item->label = 'Item ' . $i;
            $category->items[] = $item;
        }
        return $category;
    }

    /**
     * Returns the minimum pair co-occurrence in generated sets.
     *
     * @param array $sets Generated sets.
     * @param int $itemcount Number of items.
     * @return int
     */
    private function minimum_pair_count(array $sets, int $itemcount): int {
        $counts = [];
        for ($i = 0; $i < $itemcount; $i++) {
            for ($j = $i + 1; $j < $itemcount; $j++) {
                $counts['item_' . $i . '|item_' . $j] = 0;
            }
        }

        foreach ($sets as $set) {
            $ids = array_map(static fn($item) => $item->id, $set->items);
            sort($ids);
            $count = count($ids);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $key = $ids[$i] . '|' . $ids[$j];
                    $counts[$key]++;
                }
            }
        }

        return min($counts);
    }

    /**
     * Six 5-item screens cover every pair among 10 items at least once.
     */
    public function test_ten_by_five_six_screens_cover_all_pairs(): void {
        $category = $this->make_category(10);
        $sets = choice_set_generator::generate_for_category($category, 5, 6, 1234);

        $this->assertCount(6, $sets);
        $this->assertSame(1, $this->minimum_pair_count($sets, 10));
    }

    /**
     * Exact milestone designs provide increasing minimum pair exposure.
     */
    public function test_ten_by_five_multicover_milestones(): void {
        $category = $this->make_category(10);

        foreach ([10 => 2, 14 => 3, 18 => 4] as $rounds => $minimum) {
            $sets = choice_set_generator::generate_for_category($category, 5, $rounds, 4321);
            $this->assertSame($minimum, $this->minimum_pair_count($sets, 10));
        }
    }

    /**
     * Generation remains deterministic for the same seed.
     */
    public function test_generation_is_deterministic(): void {
        $category = $this->make_category(10);
        $first = choice_set_generator::generate_for_category($category, 5, 8, 99);
        $second = choice_set_generator::generate_for_category($category, 5, 8, 99);

        $firstjson = array_map([choice_set_generator::class, 'set_to_json'], $first);
        $secondjson = array_map([choice_set_generator::class, 'set_to_json'], $second);
        $this->assertSame($firstjson, $secondjson);
    }
}
