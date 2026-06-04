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
 * Privacy API implementation for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_passionfinder\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_passionfinder.
 *
 * @package    mod_passionfinder
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describes the user data stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection Updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('passionfinder_attempts', [
            'passionfinderid' => 'privacy:metadata:attempts:passionfinderid',
            'userid' => 'privacy:metadata:attempts:userid',
            'status' => 'privacy:metadata:attempts:status',
            'currentset' => 'privacy:metadata:attempts:currentset',
            'timecreated' => 'privacy:metadata:attempts:timecreated',
            'timemodified' => 'privacy:metadata:attempts:timemodified',
            'completedtime' => 'privacy:metadata:attempts:completedtime',
        ], 'privacy:metadata:attempts');

        $collection->add_database_table('passionfinder_choices', [
            'attemptid' => 'privacy:metadata:choices:attemptid',
            'setid' => 'privacy:metadata:choices:setid',
            'categoryid' => 'privacy:metadata:choices:categoryid',
            'mostitemid' => 'privacy:metadata:choices:mostitemid',
            'leastitemid' => 'privacy:metadata:choices:leastitemid',
            'timecreated' => 'privacy:metadata:choices:timecreated',
            'timemodified' => 'privacy:metadata:choices:timemodified',
        ], 'privacy:metadata:choices');

        $collection->add_database_table('passionfinder_results', [
            'attemptid' => 'privacy:metadata:results:attemptid',
            'categoryid' => 'privacy:metadata:results:categoryid',
            'itemid' => 'privacy:metadata:results:itemid',
            'appearances' => 'privacy:metadata:results:appearances',
            'mostcount' => 'privacy:metadata:results:mostcount',
            'leastcount' => 'privacy:metadata:results:leastcount',
            'netscore' => 'privacy:metadata:results:netscore',
            'normalisedscore' => 'privacy:metadata:results:normalisedscore',
            'rank' => 'privacy:metadata:results:rank',
            'timecreated' => 'privacy:metadata:results:timecreated',
        ], 'privacy:metadata:results');

        return $collection;
    }

    /**
     * Gets contexts that contain data for a user.
     *
     * @param int $userid User id.
     * @return contextlist Context list.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {passionfinder} pf ON pf.id = cm.instance
                  JOIN {passionfinder_attempts} pa ON pa.passionfinderid = pf.id
                 WHERE pa.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'passionfinder',
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Adds users with data in the specified context.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT pa.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {passionfinder} pf ON pf.id = cm.instance
                  JOIN {passionfinder_attempts} pa ON pa.passionfinderid = pf.id
                 WHERE cm.id = :cmid";

        $params = [
            'modname' => 'passionfinder',
            'cmid' => $context->instanceid,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Exports user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $contexts = $contextlist->get_contexts();
        $contextids = [];
        $contextbycmid = [];

        foreach ($contexts as $context) {
            if ($context instanceof \context_module) {
                $contextids[] = $context->id;
                $contextbycmid[$context->instanceid] = $context;
            }
        }

        if (empty($contextids)) {
            return;
        }

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctxid');
        $contextparams['contextlevel'] = CONTEXT_MODULE;
        $contextparams['modname'] = 'passionfinder';

        $activitysql = "SELECT cm.id AS cmid,
                               ctx.id AS contextid,
                               pf.id AS passionfinderid,
                               pf.name
                          FROM {context} ctx
                          JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                          JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                          JOIN {passionfinder} pf ON pf.id = cm.instance
                         WHERE ctx.id {$contextsql}";

        $activities = $DB->get_records_sql($activitysql, $contextparams);

        if (empty($activities)) {
            return;
        }

        $passionfinderids = array_map(static function($activity): int {
            return (int) $activity->passionfinderid;
        }, $activities);

        [$pfsql, $pfparams] = $DB->get_in_or_equal($passionfinderids, SQL_PARAMS_NAMED, 'pfid');
        $pfparams['userid'] = $userid;

        $attemptsql = "SELECT *
                         FROM {passionfinder_attempts}
                        WHERE userid = :userid
                          AND passionfinderid {$pfsql}
                     ORDER BY passionfinderid ASC, timecreated ASC, id ASC";

        $attempts = $DB->get_records_sql($attemptsql, $pfparams);
        $attemptids = array_keys($attempts);

        $choicesbyattempt = [];
        $resultsbyattempt = [];

        if (!empty($attemptids)) {
            [$attemptsqlin, $attemptparams] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'attemptid');

            $choices = $DB->get_records_select(
                'passionfinder_choices',
                "attemptid {$attemptsqlin}",
                $attemptparams,
                'attemptid ASC, timecreated ASC, id ASC'
            );

            foreach ($choices as $choice) {
                $choicesbyattempt[$choice->attemptid][] = $choice;
            }

            $results = $DB->get_records_select(
                'passionfinder_results',
                "attemptid {$attemptsqlin}",
                $attemptparams,
                'attemptid ASC, categoryid ASC, rank ASC, id ASC'
            );

            foreach ($results as $result) {
                $resultsbyattempt[$result->attemptid][] = $result;
            }
        }

        $attemptsbyactivity = [];

        foreach ($attempts as $attempt) {
            $attemptsbyactivity[$attempt->passionfinderid][] = $attempt;
        }

        foreach ($activities as $activity) {
            if (empty($attemptsbyactivity[$activity->passionfinderid])) {
                continue;
            }

            if (empty($contextbycmid[$activity->cmid])) {
                continue;
            }

            $exportattempts = [];

            foreach ($attemptsbyactivity[$activity->passionfinderid] as $attempt) {
                $attemptdata = (object) [
                    'status' => $attempt->status,
                    'currentset' => $attempt->currentset,
                    'timecreated' => transform::datetime($attempt->timecreated),
                    'timemodified' => transform::datetime($attempt->timemodified),
                    'completedtime' => !empty($attempt->completedtime) ? transform::datetime($attempt->completedtime) : null,
                    'choices' => [],
                    'results' => [],
                ];

                foreach ($choicesbyattempt[$attempt->id] ?? [] as $choice) {
                    $attemptdata->choices[] = (object) [
                        'categoryid' => $choice->categoryid,
                        'mostitemid' => $choice->mostitemid,
                        'leastitemid' => $choice->leastitemid,
                        'timecreated' => transform::datetime($choice->timecreated),
                        'timemodified' => transform::datetime($choice->timemodified),
                    ];
                }

                foreach ($resultsbyattempt[$attempt->id] ?? [] as $result) {
                    $attemptdata->results[] = (object) [
                        'categoryid' => $result->categoryid,
                        'itemid' => $result->itemid,
                        'appearances' => $result->appearances,
                        'mostcount' => $result->mostcount,
                        'leastcount' => $result->leastcount,
                        'netscore' => $result->netscore,
                        'normalisedscore' => $result->normalisedscore,
                        'rank' => $result->rank,
                        'timecreated' => transform::datetime($result->timecreated),
                    ];
                }

                $exportattempts[] = $attemptdata;
            }

            $subcontext = [
                get_string('pluginname', 'mod_passionfinder'),
                format_string($activity->name),
            ];

            writer::with_context($contextbycmid[$activity->cmid])->export_data($subcontext, (object) [
                'attempts' => $exportattempts,
            ]);
        }

        foreach ($contexts as $context) {
            if ($context instanceof \context_module) {
                helper::export_context_files($context, $contextlist->get_user());
            }
        }
    }

    /**
     * Deletes all user data for a context.
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if (!$context instanceof \context_module) {
            return;
        }

        self::delete_attempts_for_context($context);
    }

    /**
     * Deletes user data for the approved context list.
     *
     * @param approved_contextlist $contextlist Approved context list.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_module) {
                self::delete_attempts_for_context($context, [$userid]);
            }
        }
    }

    /**
     * Deletes user data for an approved user list.
     *
     * @param approved_userlist $userlist Approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        self::delete_attempts_for_context($context, $userlist->get_userids());
    }

    /**
     * Deletes attempts and related rows for a context and optional user list.
     *
     * @param \context_module $context Module context.
     * @param array|null $userids Optional user ids.
     * @return void
     */
    private static function delete_attempts_for_context(\context_module $context, ?array $userids = null): void {
        global $DB;

        $attemptparams = ['passionfinderid' => $context->instanceid];
        $select = 'passionfinderid = :passionfinderid';

        if ($userids !== null) {
            if (empty($userids)) {
                return;
            }

            [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');
            $select .= " AND userid {$usersql}";
            $attemptparams = array_merge($attemptparams, $userparams);
        }

        $attempts = $DB->get_records_select('passionfinder_attempts', $select, $attemptparams, '', 'id');

        if (empty($attempts)) {
            return;
        }

        $attemptids = array_keys($attempts);
        [$attemptsql, $attemptparams] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'attemptid');

        $DB->delete_records_select('passionfinder_choices', "attemptid {$attemptsql}", $attemptparams);
        $DB->delete_records_select('passionfinder_results', "attemptid {$attemptsql}", $attemptparams);
        $DB->delete_records_select('passionfinder_sets', "attemptid {$attemptsql}", $attemptparams);
        $DB->delete_records_select('passionfinder_attempts', "id {$attemptsql}", $attemptparams);
    }
}
