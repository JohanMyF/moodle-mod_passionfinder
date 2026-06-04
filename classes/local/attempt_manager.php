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
 * Attempt manager for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_passionfinder\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates, retrieves, and updates learner attempts.
 *
 * The attempt manager freezes generated choice sets for a respondent. This prevents
 * page refreshes, later teacher edits, or JSON changes from altering the sequence
 * of choices inside an already-started attempt.
 *
 * @package    mod_passionfinder
 */
class attempt_manager {
    /** @var string Attempt is still editable. */
    public const STATUS_IN_PROGRESS = 'inprogress';

    /** @var string Attempt has been submitted and should be locked. */
    public const STATUS_COMPLETED = 'completed';

    /**
     * Gets the current attempt for a user, or creates one if none exists.
     *
     * @param \stdClass $passionfinder Activity instance.
     * @param \stdClass $cm Course module.
     * @param int $userid User id.
     * @return \stdClass Attempt record.
     */
    public static function get_or_create_attempt(\stdClass $passionfinder, \stdClass $cm, int $userid): \stdClass {
        global $DB;

        $attempt = $DB->get_record('passionfinder_attempts', [
            'passionfinderid' => $passionfinder->id,
            'userid' => $userid,
        ]);

        if ($attempt) {
            return $attempt;
        }

        return self::create_attempt($passionfinder, $cm, $userid);
    }

    /**
     * Creates a new attempt and freezes generated choice sets.
     *
     * @param \stdClass $passionfinder Activity instance.
     * @param \stdClass $cm Course module.
     * @param int $userid User id.
     * @return \stdClass Attempt record.
     */
    public static function create_attempt(\stdClass $passionfinder, \stdClass $cm, int $userid): \stdClass {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $now = time();

        $attemptrecord = new \stdClass();
        $attemptrecord->passionfinderid = $passionfinder->id;
        $attemptrecord->userid = $userid;
        $attemptrecord->status = self::STATUS_IN_PROGRESS;
        $attemptrecord->currentset = 1;
        $attemptrecord->timecreated = $now;
        $attemptrecord->timemodified = $now;
        $attemptrecord->completedtime = null;

        $attemptid = $DB->insert_record('passionfinder_attempts', $attemptrecord);

        $instrument = instrument_parser::parse((string) $passionfinder->instrumentjson, $passionfinder);
        $sets = choice_set_generator::generate($instrument, self::make_seed((int) $cm->id, $userid));

        foreach ($sets as $set) {
            $setrecord = new \stdClass();
            $setrecord->attemptid = $attemptid;
            $setrecord->categoryid = $set->categoryid;
            $setrecord->setnumber = $set->setnumber;
            $setrecord->itemsjson = choice_set_generator::set_to_json($set);
            $setrecord->timecreated = $now;

            $DB->insert_record('passionfinder_sets', $setrecord);
        }

        $transaction->allow_commit();

        return $DB->get_record('passionfinder_attempts', ['id' => $attemptid], '*', MUST_EXIST);
    }

    /**
     * Gets all frozen sets for an attempt.
     *
     * @param int $attemptid Attempt id.
     * @return array Set records with decoded setdata property.
     */
    public static function get_attempt_sets(int $attemptid): array {
        global $DB;

        $records = $DB->get_records('passionfinder_sets', ['attemptid' => $attemptid], 'setnumber ASC, id ASC');

        foreach ($records as $record) {
            $decoded = json_decode($record->itemsjson);
            $record->setdata = json_last_error() === JSON_ERROR_NONE && is_object($decoded) ? $decoded : null;
        }

        return $records;
    }

    /**
     * Gets a single frozen set by its attempt-relative set number.
     *
     * @param int $attemptid Attempt id.
     * @param int $setnumber Set number.
     * @return \stdClass|null Set record with decoded setdata property.
     */
    public static function get_set_by_number(int $attemptid, int $setnumber): ?\stdClass {
        global $DB;

        $record = $DB->get_record('passionfinder_sets', [
            'attemptid' => $attemptid,
            'setnumber' => $setnumber,
        ]);

        if (!$record) {
            return null;
        }

        $decoded = json_decode($record->itemsjson);
        $record->setdata = json_last_error() === JSON_ERROR_NONE && is_object($decoded) ? $decoded : null;

        return $record;
    }

    /**
     * Saves or updates a respondent's choice for a set.
     *
     * @param \stdClass $attempt Attempt record.
     * @param \stdClass $set Set record.
     * @param string $mostitemid Most-selected item id.
     * @param string $leastitemid Least-selected item id.
     * @return \stdClass Choice record.
     * @throws \moodle_exception
     */
    public static function save_choice(\stdClass $attempt, \stdClass $set, string $mostitemid, string $leastitemid): \stdClass {
        global $DB;

        if ($attempt->status !== self::STATUS_IN_PROGRESS) {
            throw new \moodle_exception('attemptlocked', 'mod_passionfinder');
        }

        $mostitemid = self::clean_identifier($mostitemid);
        $leastitemid = self::clean_identifier($leastitemid);

        if ($mostitemid === '' || $leastitemid === '') {
            throw new \moodle_exception('choiceincomplete', 'mod_passionfinder');
        }

        if ($mostitemid === $leastitemid) {
            throw new \moodle_exception('choicesameitem', 'mod_passionfinder');
        }

        if (!self::set_contains_item($set, $mostitemid) || !self::set_contains_item($set, $leastitemid)) {
            throw new \moodle_exception('choiceinvaliditem', 'mod_passionfinder');
        }

        $now = time();

        $choice = $DB->get_record('passionfinder_choices', [
            'attemptid' => $attempt->id,
            'setid' => $set->id,
        ]);

        if ($choice) {
            $choice->categoryid = $set->categoryid;
            $choice->mostitemid = $mostitemid;
            $choice->leastitemid = $leastitemid;
            $choice->timemodified = $now;

            $DB->update_record('passionfinder_choices', $choice);

            return $DB->get_record('passionfinder_choices', ['id' => $choice->id], '*', MUST_EXIST);
        }

        $choice = new \stdClass();
        $choice->attemptid = $attempt->id;
        $choice->setid = $set->id;
        $choice->categoryid = $set->categoryid;
        $choice->mostitemid = $mostitemid;
        $choice->leastitemid = $leastitemid;
        $choice->timecreated = $now;
        $choice->timemodified = $now;

        $choice->id = $DB->insert_record('passionfinder_choices', $choice);

        return $choice;
    }

    /**
     * Gets choices for an attempt, keyed by set id.
     *
     * @param int $attemptid Attempt id.
     * @return array Choice records keyed by set id.
     */
    public static function get_choices_by_set(int $attemptid): array {
        global $DB;

        $choices = $DB->get_records('passionfinder_choices', ['attemptid' => $attemptid], '', '*');
        $byset = [];

        foreach ($choices as $choice) {
            $byset[(int) $choice->setid] = $choice;
        }

        return $byset;
    }

    /**
     * Returns basic progress information for an attempt.
     *
     * @param int $attemptid Attempt id.
     * @return \stdClass Progress object.
     */
    public static function get_progress(int $attemptid): \stdClass {
        global $DB;

        $progress = new \stdClass();
        $progress->totalsets = (int) $DB->count_records('passionfinder_sets', ['attemptid' => $attemptid]);
        $progress->answeredsets = (int) $DB->count_records('passionfinder_choices', ['attemptid' => $attemptid]);
        $progress->remainingsets = max(0, $progress->totalsets - $progress->answeredsets);
        $progress->complete = $progress->totalsets > 0 && $progress->answeredsets >= $progress->totalsets;

        return $progress;
    }

    /**
     * Marks an attempt as completed if all sets have choices.
     *
     * @param \stdClass $attempt Attempt record.
     * @return \stdClass Updated attempt record.
     * @throws \moodle_exception
     */
    public static function complete_attempt(\stdClass $attempt): \stdClass {
        global $DB;

        $progress = self::get_progress((int) $attempt->id);

        if (!$progress->complete) {
            throw new \moodle_exception('attemptnotcomplete', 'mod_passionfinder');
        }

        $attempt->status = self::STATUS_COMPLETED;
        $attempt->completedtime = time();
        $attempt->timemodified = $attempt->completedtime;

        $DB->update_record('passionfinder_attempts', $attempt);

        return $DB->get_record('passionfinder_attempts', ['id' => $attempt->id], '*', MUST_EXIST);
    }

    /**
     * Checks whether a frozen set contains an item id.
     *
     * @param \stdClass $set Set record with setdata.
     * @param string $itemid Item id.
     * @return bool
     */
    private static function set_contains_item(\stdClass $set, string $itemid): bool {
        if (empty($set->setdata) || empty($set->setdata->items) || !is_array($set->setdata->items)) {
            return false;
        }

        foreach ($set->setdata->items as $item) {
            if (isset($item->id) && $item->id === $itemid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cleans an item identifier.
     *
     * @param string $value Raw value.
     * @return string Clean identifier.
     */
    private static function clean_identifier(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = preg_replace('/_+/', '_', $value);
        $value = trim($value, '_');

        return substr($value, 0, 100);
    }

    /**
     * Makes a stable seed.
     *
     * @param int $cmid Course module id.
     * @param int $userid User id.
     * @return int Stable seed.
     */
    private static function make_seed(int $cmid, int $userid): int {
        $seed = ($cmid * 1000003) + ($userid * 9176);

        return max(1, abs($seed));
    }
}
