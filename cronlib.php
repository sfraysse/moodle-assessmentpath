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


/**
 * Execute CRON task.
 */

function assessmentpath_cron() {
	assessmentpath_cron_process_notifications_queue();
}

/**
 * Process notifications queue.
 */
function assessmentpath_cron_process_notifications_queue()
{
	global $DB;
	$records = $DB->get_records('assessmentpath_notif_queue');
	foreach ($records as $record) {

		// Get data
		if (!$cm = $DB->get_record("course_modules", array("id" => $record->cmid))) return true;
		if (!$module = $DB->get_record('modules', array('id' => $cm->module))) return true;
		if (!$activity = $DB->get_record('assessmentpath', array('id' => $cm->instance))) return true;
		if (!$course = $DB->get_record("course", array("id" => $cm->course))) return true;
		if (!$submitter = $DB->get_record("user", array("id" => $record->submitterid))) return true;
		if (!$recipient = $DB->get_record("user", array("id" => $record->recipientid))) return true;

		// Send notifications
		$sent = assessmentpath_cron_prepare_and_send_notification(
			$course,
			$activity,
			$submitter,
			$recipient,
			$cm
		);

		// Remove from queue
		if ($sent) {
			$DB->delete_records('assessmentpath_notif_queue', ['id' => $record->id]);
		}
	}
}

/**
 * Sends notifications.
 */
function assessmentpath_cron_prepare_and_send_notification($course, $activity, $submitter, $recipient, $cm)
{
	global $CFG, $DB;

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
	$a->activitylink = '<a href="' . $a->activityurl . '">' . $activity->name . '</a>';

	// Student who sat the activity info.
	$a->studentidnumber = $submitter->idnumber;
	$a->studentname = fullname($submitter);
	$a->studentusername = $submitter->username;

	// Send notifications.
	return assessmentpath_cron_send_notification($recipient, $submitter, $a);
}

/**
 * Sends notification message.
 */
function assessmentpath_cron_send_notification($recipient, $submitter, $a)
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


