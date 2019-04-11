<?php

/* * *************************************************************
 *  This script has been developed for Moodle - http://moodle.org/
 *
 *  You can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * ************************************************************* */

defined('MOODLE_INTERNAL') || die();

class mod_assessmentpath_observer {

    /**
     * Activity completed.
     */
    public static function course_module_completion_updated(\core\event\course_module_completion_updated $event) {
        global $DB;

        // Check that this is an assessment path event
        $eventdata = $event->get_record_snapshot('course_modules_completion', $event->objectid);
        $cmid = $eventdata->coursemoduleid;
        if (!$cm = $DB->get_record("course_modules", array("id" => $cmid))) return true;
        if (!$module = $DB->get_record('modules', array('id' => $cm->module))) return true;
        if ($module->name != 'assessmentpath') return true;

        // Check completion status
        if ($eventdata->completionstate != COMPLETION_COMPLETE) return true;

        // Get users to notify
        $context = context_module::instance($cmid);
        $userstonotify = get_users_by_capability($context, 'mod/assessmentpath:notifycompletion');
        if (empty($userstonotify)) return true;

        // Queue the notifications
        foreach ($userstonotify as $recipient) {
            $record = new StdClass();
            $record->cmid = $cmid;
            $record->submitterid = $eventdata->userid;
            $record->recipientid = $recipient->id;
            $record->notification = 'completion';
            $DB->insert_record('assessmentpath_notif_queue', $record);
        }

        return true;
    }

}
