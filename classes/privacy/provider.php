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

namespace mod_assessmentpath\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy class for requesting user data.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider
{

    /**
     * Return the fields which contain personal data.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection
    {
        $collection->add_database_table('scormlite_scoes_track', [
            'userid' => 'privacy:metadata:scoes_track:userid',
            'attempt' => 'privacy:metadata:scoes_track:attempt',
            'element' => 'privacy:metadata:scoes_track:element',
            'value' => 'privacy:metadata:scoes_track:value',
            'timemodified' => 'privacy:metadata:scoes_track:timemodified'
        ], 'privacy:metadata:scormlite_scoes_track');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist
    {
        $contextlist = new contextlist();

        // Select from SCORMLite tracks
        $sql = "SELECT ctx.id
                  FROM {%s} sst
                  JOIN {assessmentpath_tests} test
                    ON test.sco = sst.scoid
                  JOIN {assessmentpath_steps} step
                    ON step.id = test.step                    
                  JOIN {modules} m
                    ON m.name = 'assessmentpath'
                  JOIN {course_modules} cm
                    ON cm.instance = step.activity
                   AND cm.module = m.id
                  JOIN {context} ctx
                    ON ctx.instanceid = cm.id
                   AND ctx.contextlevel = :modlevel
                 WHERE sst.userid = :userid";

        $params = ['modlevel' => CONTEXT_MODULE, 'userid' => $userid];
        $contextlist->add_from_sql(sprintf($sql, 'scormlite_scoes_track'), $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        // Select from SCORMLite tracks
        $sql = "SELECT sst.userid
                  FROM {%s} sst
                  JOIN {assessmentpath_tests} test
                    ON test.sco = sst.scoid
                  JOIN {assessmentpath_steps} step
                    ON step.id = test.step
                  JOIN {modules} m
                    ON m.name = 'assessmentpath'
                  JOIN {course_modules} cm
                    ON cm.instance = step.activity
                   AND cm.module = m.id
                  JOIN {context} ctx
                    ON ctx.instanceid = cm.id
                   AND ctx.contextlevel = :modlevel
                 WHERE ctx.id = :contextid";
                 
        $params = ['modlevel' => CONTEXT_MODULE, 'contextid' => $context->id];
        $userlist->add_from_sql('userid', sprintf($sql, 'scormlite_scoes_track'), $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist)
    {
        global $DB;
        $userid = $contextlist->get_user()->id;

        // Remove contexts different from CONTEXT_MODULE.
        $contexts = array_reduce($contextlist->get_contexts(), function ($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->id;
            }
            return $carry;
        }, []);

        if (empty($contexts)) {
            return;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($contexts, SQL_PARAMS_NAMED);

        // Get scoes_track data.
        $sql = "SELECT sst.id,
                       sst.attempt,
                       sst.element,
                       sst.value,
                       sst.timemodified,
                       step.position,
                       test.remediation,
                       ctx.id as contextid
                  FROM {scormlite_scoes_track} sst
                  JOIN {assessmentpath_tests} test
                    ON test.sco = sst.scoid
                  JOIN {assessmentpath_steps} step
                    ON step.id = test.step
                  JOIN {modules} m
                    ON m.name = 'assessmentpath'
                  JOIN {course_modules} cm
                    ON cm.instance = step.activity
                   AND cm.module = m.id
                  JOIN {context} ctx
                    ON ctx.instanceid = cm.id
                 WHERE ctx.id $insql
                   AND sst.userid = :userid
                ";
        $params = array_merge($inparams, ['userid' => $userid]);

        $alldata = [];
        $scoestracks = $DB->get_recordset_sql($sql, $params);
        foreach ($scoestracks as $track) {
            $alldata[$track->contextid][$track->position][$track->remediation][$track->attempt][] = (object)[
                'element' => $track->element,
                'value' => $track->value,
                'timemodified' => transform::datetime($track->timemodified),
            ];
        }
        $scoestracks->close();

        // Push in folders
        array_walk($alldata, function ($stepsdata, $contextid) {
            $context = \context::instance_by_id($contextid);
            array_walk($stepsdata, function ($stepdata, $position) use ($context) {
                array_walk($stepdata, function ($testdata, $remediation) use ($context, $position) {
                    array_walk($testdata, function ($attemptdata, $attempt) use ($context, $position, $remediation) {
                        $subcontext = [
                            get_string('steps', 'assessmentpath'),
                            get_string('step', 'assessmentpath') . ' ' . ($position+1),
                            $remediation ? get_string('remediationtest', 'assessmentpath') : get_string('initialtest', 'assessmentpath'),
                            get_string('myattempts', 'scorm'),
                            get_string('attempt', 'scorm') . " $attempt"
                        ];
                        writer::with_context($context)->export_data(
                            $subcontext,
                            (object) ['scoestrack' => $attemptdata]
                        );
                    });
                });
            });
        });
    }

    /**
     * Delete all user data which matches the specified context.
     *
     * @param context $context A user context.
     */
    public static function delete_data_for_all_users_in_context(\context $context)
    {
        // This should not happen, but just in case.
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        // Delete SCORMLite tracks
        $sql = "SELECT sst.id
                  FROM {%s} sst
                  JOIN {assessmentpath_tests} test
                    ON test.sco = sst.scoid
                  JOIN {assessmentpath_steps} step
                    ON step.id = test.step
                  JOIN {modules} m
                    ON m.name = 'assessmentpath'
                  JOIN {course_modules} cm
                    ON cm.instance = step.activity
                   AND cm.module = m.id
                 WHERE cm.id = :cmid";

        $params = ['cmid' => $context->instanceid];
        static::delete_data('scormlite_scoes_track', $sql, $params);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist)
    {
        global $DB;
        $userid = $contextlist->get_user()->id;

        // Remove contexts different from CONTEXT_MODULE.
        $contextids = array_reduce($contextlist->get_contexts(), function ($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->id;
            }
            return $carry;
        }, []);

        if (empty($contextids)) {
            return;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);

        // Delete SCORMLite tracks
        $sql = "SELECT sst.id
                  FROM {%s} sst
                  JOIN {assessmentpath_tests} test
                    ON test.sco = sst.scoid
                  JOIN {assessmentpath_steps} step
                    ON step.id = test.step
                  JOIN {modules} m
                    ON m.name = 'assessmentpath'
                  JOIN {course_modules} cm
                    ON cm.instance = step.activity
                   AND cm.module = m.id
                  JOIN {context} ctx
                    ON ctx.instanceid = cm.id
                 WHERE sst.userid = :userid
                   AND ctx.id $insql";

        $params = array_merge($inparams, ['userid' => $userid]);
        static::delete_data('scormlite_scoes_track', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (!is_a($context, \context_module::class)) {
            return;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete SCORMLite tracks
        $sql = "SELECT sst.id
                FROM {%s} sst
                JOIN {assessmentpath_tests} test
                    ON test.sco = sst.scoid
                JOIN {assessmentpath_steps} step
                    ON step.id = test.step
                JOIN {modules} m
                    ON m.name = 'assessmentpath'
                JOIN {course_modules} cm
                    ON cm.instance = step.activity
                   AND cm.module = m.id
                JOIN {context} ctx
                    ON ctx.instanceid = cm.id
                WHERE ctx.id = :contextid
                    AND sst.userid $insql";

        $params = array_merge($inparams, ['contextid' => $context->id]);
        static::delete_data('scormlite_scoes_track', $sql, $params);
    }

    /**
     * Delete data from $tablename with the IDs returned by $sql query.
     *
     * @param  string $tablename  Table name where executing the SQL query.
     * @param  string $sql    SQL query for getting the IDs of the scoestrack entries to delete.
     * @param  array  $params SQL params for the query.
     */
    protected static function delete_data(string $tablename, string $sql, array $params)
    {
        global $DB;
        $ids = $DB->get_fieldset_sql(sprintf($sql, $tablename), $params);
        if (!empty($ids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select($tablename, "id $insql", $inparams);
        }
    }
}
