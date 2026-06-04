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
 * Parser and validator for PassionFinder JSON instruments.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_passionfinder\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Parses, normalises, and validates a PassionFinder instrument JSON document.
 *
 * This class does not save anything to the database. It only turns teacher-supplied
 * JSON into a predictable PHP object that later classes can use safely.
 *
 * @package    mod_passionfinder
 */
class instrument_parser {
    /** @var int Minimum number of categories in an instrument. */
    private const MIN_CATEGORIES = 1;

    /** @var int Minimum number of items in each category. */
    private const MIN_ITEMS_PER_CATEGORY = 3;

    /** @var int Maximum length for generated identifiers. */
    private const MAX_ID_LENGTH = 100;

    /**
     * Parses and validates raw JSON.
     *
     * @param string $json Raw JSON string.
     * @param \stdClass|null $activity Optional activity record for fallback settings.
     * @return \stdClass Normalised instrument object.
     * @throws \moodle_exception When the JSON is invalid or incomplete.
     */
    public static function parse(string $json, ?\stdClass $activity = null): \stdClass {
        $json = trim($json);

        if ($json === '') {
            throw new \moodle_exception('parsererroremptyjson', 'mod_passionfinder');
        }

        $instrument = json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE || !is_object($instrument)) {
            throw new \moodle_exception('parsererrorinvalidjson', 'mod_passionfinder', '', json_last_error_msg());
        }

        return self::normalise_instrument($instrument, $activity);
    }

    /**
     * Returns whether raw JSON is valid for a PassionFinder instrument.
     *
     * @param string $json Raw JSON string.
     * @param \stdClass|null $activity Optional activity record for fallback settings.
     * @return array Validation result with keys valid, message, and instrument.
     */
    public static function validate(string $json, ?\stdClass $activity = null): array {
        try {
            $instrument = self::parse($json, $activity);

            return [
                'valid' => true,
                'message' => get_string('jsonvalid', 'mod_passionfinder'),
                'instrument' => $instrument,
            ];
        } catch (\moodle_exception $exception) {
            return [
                'valid' => false,
                'message' => $exception->getMessage(),
                'instrument' => null,
            ];
        }
    }

    /**
     * Normalises a decoded instrument object.
     *
     * @param \stdClass $instrument Decoded JSON object.
     * @param \stdClass|null $activity Optional activity record.
     * @return \stdClass Normalised instrument object.
     * @throws \moodle_exception
     */
    private static function normalise_instrument(\stdClass $instrument, ?\stdClass $activity = null): \stdClass {
        $normalised = new \stdClass();

        $normalised->metadata = self::normalise_metadata($instrument);
        $normalised->settings = self::normalise_settings($instrument, $activity);
        $normalised->categories = self::normalise_categories($instrument, $normalised->settings);

        if (count($normalised->categories) < self::MIN_CATEGORIES) {
            throw new \moodle_exception('parsererrornocategories', 'mod_passionfinder');
        }

        return $normalised;
    }

    /**
     * Normalises metadata.
     *
     * @param \stdClass $instrument Decoded JSON object.
     * @return \stdClass Metadata object.
     */
    private static function normalise_metadata(\stdClass $instrument): \stdClass {
        $metadata = new \stdClass();
        $rawmetadata = self::object_property($instrument, 'metadata');

        $metadata->schema = self::clean_text(self::object_property($rawmetadata, 'schema', 'mod_passionfinder'));
        $metadata->schemaVersion = (int) self::object_property($rawmetadata, 'schemaVersion', 1);
        $metadata->title = self::clean_text(self::object_property($rawmetadata, 'title', get_string('pluginname', 'mod_passionfinder')));
        $metadata->description = self::clean_text(self::object_property($rawmetadata, 'description', ''));
        $metadata->createdBy = self::clean_text(self::object_property($rawmetadata, 'createdBy', ''));
        $metadata->containsStudentData = (bool) self::object_property($rawmetadata, 'containsStudentData', false);

        return $metadata;
    }

    /**
     * Normalises instrument settings.
     *
     * @param \stdClass $instrument Decoded JSON object.
     * @param \stdClass|null $activity Optional activity record.
     * @return \stdClass Settings object.
     */
    private static function normalise_settings(\stdClass $instrument, ?\stdClass $activity = null): \stdClass {
        $rawsettings = self::object_property($instrument, 'settings');

        $settings = new \stdClass();
        $settings->itemsperround = self::bounded_int(
            self::object_property($rawsettings, 'itemsperround', $activity->itemsperround ?? 5),
            3,
            6,
            5
        );
        $settings->roundspercategory = self::bounded_int(
            self::object_property($rawsettings, 'roundspercategory', $activity->roundspercategory ?? 8),
            1,
            50,
            8
        );
        $settings->resultdepth = self::bounded_int(
            self::object_property($rawsettings, 'resultdepth', $activity->resultdepth ?? 5),
            1,
            20,
            5
        );
        $settings->mostlabel = self::clean_text(
            self::object_property($rawsettings, 'mostlabel', $activity->mostlabel ?? get_string('defaultmostlabel', 'mod_passionfinder'))
        );
        $settings->leastlabel = self::clean_text(
            self::object_property($rawsettings, 'leastlabel', $activity->leastlabel ?? get_string('defaultleastlabel', 'mod_passionfinder'))
        );

        if ($settings->mostlabel === '') {
            $settings->mostlabel = get_string('defaultmostlabel', 'mod_passionfinder');
        }

        if ($settings->leastlabel === '') {
            $settings->leastlabel = get_string('defaultleastlabel', 'mod_passionfinder');
        }

        return $settings;
    }

    /**
     * Normalises categories and their items.
     *
     * @param \stdClass $instrument Decoded JSON object.
     * @param \stdClass $settings Normalised settings.
     * @return array Category objects.
     * @throws \moodle_exception
     */
    private static function normalise_categories(\stdClass $instrument, \stdClass $settings): array {
        if (empty($instrument->categories) || !is_array($instrument->categories)) {
            throw new \moodle_exception('parsererrornocategories', 'mod_passionfinder');
        }

        $categories = [];
        $categoryids = [];

        foreach ($instrument->categories as $index => $rawcategory) {
            if (!is_object($rawcategory)) {
                throw new \moodle_exception('parsererrorinvalidcategory', 'mod_passionfinder', '', $index + 1);
            }

            $category = new \stdClass();
            $category->id = self::make_identifier(self::object_property($rawcategory, 'id', 'category_' . ($index + 1)));
            $category->name = self::clean_text(self::object_property($rawcategory, 'name', ''));
            $category->prompt = self::clean_text(self::object_property($rawcategory, 'prompt', ''));
            $category->mostlabel = self::clean_text(self::object_property($rawcategory, 'mostlabel', $settings->mostlabel));
            $category->leastlabel = self::clean_text(self::object_property($rawcategory, 'leastlabel', $settings->leastlabel));
            $category->items = self::normalise_items($rawcategory, $category->id);

            if ($category->id === '' || $category->name === '' || $category->prompt === '') {
                throw new \moodle_exception('parsererrorinvalidcategory', 'mod_passionfinder', '', $index + 1);
            }

            if (isset($categoryids[$category->id])) {
                throw new \moodle_exception('parsererrorduplicatecategory', 'mod_passionfinder', '', $category->id);
            }

            if ($category->mostlabel === '') {
                $category->mostlabel = $settings->mostlabel;
            }

            if ($category->leastlabel === '') {
                $category->leastlabel = $settings->leastlabel;
            }

            if (count($category->items) < self::MIN_ITEMS_PER_CATEGORY) {
                throw new \moodle_exception('parsererrortoofewitems', 'mod_passionfinder', '', $category->name);
            }

            $categoryids[$category->id] = true;
            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * Normalises item objects for a category.
     *
     * @param \stdClass $rawcategory Raw category object.
     * @param string $categoryid Category identifier.
     * @return array Item objects.
     * @throws \moodle_exception
     */
    private static function normalise_items(\stdClass $rawcategory, string $categoryid): array {
        if (empty($rawcategory->items) || !is_array($rawcategory->items)) {
            throw new \moodle_exception('parsererrornoitems', 'mod_passionfinder', '', $categoryid);
        }

        $items = [];
        $itemids = [];

        foreach ($rawcategory->items as $index => $rawitem) {
            if (!is_object($rawitem)) {
                throw new \moodle_exception('parsererrorinvaliditem', 'mod_passionfinder', '', $categoryid);
            }

            $item = new \stdClass();
            $item->id = self::make_identifier(self::object_property($rawitem, 'id', 'item_' . ($index + 1)));
            $item->label = self::clean_text(self::object_property($rawitem, 'label', ''));

            if ($item->id === '' || $item->label === '') {
                throw new \moodle_exception('parsererrorinvaliditem', 'mod_passionfinder', '', $categoryid);
            }

            if (isset($itemids[$item->id])) {
                throw new \moodle_exception('parsererrorduplicateitem', 'mod_passionfinder', '', $item->id);
            }

            $itemids[$item->id] = true;
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Reads an object property safely.
     *
     * @param mixed $object Object to read from.
     * @param string $property Property name.
     * @param mixed $default Default value.
     * @return mixed
     */
    private static function object_property($object, string $property, $default = null) {
        if (is_object($object) && property_exists($object, $property)) {
            return $object->{$property};
        }

        return $default;
    }

    /**
     * Cleans teacher-supplied text.
     *
     * @param mixed $value Raw value.
     * @return string Clean text.
     */
    private static function clean_text($value): string {
        return clean_param((string) $value, PARAM_TEXT);
    }

    /**
     * Creates a safe identifier.
     *
     * @param mixed $value Raw value.
     * @return string Safe identifier.
     */
    private static function make_identifier($value): string {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = preg_replace('/_+/', '_', $value);
        $value = trim($value, '_');

        return substr($value, 0, self::MAX_ID_LENGTH);
    }

    /**
     * Returns an integer constrained to a range.
     *
     * @param mixed $value Raw value.
     * @param int $min Minimum allowed.
     * @param int $max Maximum allowed.
     * @param int $default Default if invalid.
     * @return int Bounded integer.
     */
    private static function bounded_int($value, int $min, int $max, int $default): int {
        if (!is_numeric($value)) {
            return $default;
        }

        $value = (int) $value;

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }
}
