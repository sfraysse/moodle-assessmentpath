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
     * Undocumented function
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

        // Message data
        if (!$activity = $DB->get_record('assessmentpath', array('id' => $cm->instance))) return true;
        if (!$course = $DB->get_record("course", array("id" => $cm->course))) return true;
        if (!$user = $DB->get_record("user", array("id" => $eventdata->userid))) return true;

        // Needed!
        require_login($course->id, false, $cm);

        return self::send_notifications(
            $course,
            $activity,
            $user,
            context_module::instance($cm->id),
            $cm
        );
    }

    /**
     * Sends notifications.
     */
    public static function send_notifications($course, $activity, $submitter, $context, $cm)
    {
        global $CFG, $DB;

        // Get users to notify
        $userstonotify = get_users_by_capability($context, 'mod/assessmentpath:notifycompletion');
        if (empty($userstonotify)) return true;

        // Prepare template data
        $a = new stdClass();

        // Course info.
        $a->courseid = $course->id;
        $a->coursename = $course->fullname;
        $a->courseshortname = $course->shortname;

        // activity info.
        $a->activityname = $activity->name;
        $a->activityreporturl = $CFG->wwwroot . '/mod/assessmentpath/report/P3.php?id=' . $cm->id;
        $a->activityreportlink = '<a href="' . $a->activityreporturl . '">' . $a->activityname . ' </a>';
        $a->activityurl = $CFG->wwwroot . '/mod/assessmentpath/view.php?id=' . $cm->id;
        $a->activitylink = '<a href="' . $a->activityurl . '">' . format_string($activity->name) . '</a>';

        // Student who sat the activity info.
        $a->studentidnumber = $submitter->idnumber;
        $a->studentname = fullname($submitter);
        $a->studentusername = $submitter->username;

        // Send notifications.
        $allok = true;
        foreach ($userstonotify as $recipient) {
            $allok = $allok && self::send_notification($recipient, $submitter, $a);
        }
        return $allok;
    }

    /**
     * Sends notification message.
     */
    public static function send_notification($recipient, $submitter, $a)
    {
        // Recipient info for template.
        $a->useridnumber = $recipient->idnumber;
        $a->username = fullname($recipient);
        $a->userusername = $recipient->username;

        // Prepare the message.
        $eventdata = new \core\message\message();
        $eventdata->courseid = $a->courseid;
        $eventdata->component = 'mod_assessmentpath';
        $eventdata->name = 'completion';
        $eventdata->notification = 1;

        $eventdata->userfrom = $submitter;
        $eventdata->userto = $recipient;
        $eventdata->subject = get_string('emailnotifysubject', 'assessmentpath', $a);
        $eventdata->fullmessage = get_string('emailnotifybody', 'assessmentpath', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = '';

        $eventdata->smallmessage = get_string('emailnotifysmall', 'assessmentpath', $a);
        $eventdata->contexturl = $a->activityreporturl;
        $eventdata->contexturlname = $a->activityname;

        // ... and send it.
        return message_send($eventdata);
    }


}
