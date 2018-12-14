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

// Includes
require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
require_once($CFG->dirroot.'/mod/assessmentpath/report/reportlib.php');

// Params
$ids = required_param('id', PARAM_TEXT); 
$format  = optional_param('format', 'lms', PARAM_ALPHA);  // 'lms', 'csv', 'html', 'xls'

// Activity List
$ids = explode(',', $ids);
$id = $ids[0];

// Useful objects and vars
$cm = get_coursemodule_from_id('assessmentpath', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$activity = $DB->get_record('assessmentpath', array('id'=>$cm->instance), '*', MUST_EXIST);

$courseid = $course->id;
$activityid = $activity->id;

// KD2015-31 - End of "group members only" option
// $grouping = $DB->get_record('groupings', array('id'=>$cm->groupingid), 'id,name', MUST_EXIST);
// $groupingid = $cm->groupingid;
$grouping = scormlite_report_get_activity_group($cm->id);
$groupingid = null;
if (!is_null($grouping)) {
    $groupingid = $grouping->id;
    $cm->groupingid = $groupingid;
}

//
// Page setup 
//

$context = context_course::instance($course->id);
require_login($course->id, false, $cm);
require_capability('mod/scormlite:viewotherreport', $context);
$url = new moodle_url('/mod/assessmentpath/report/P3.php', array('id'=>$cm->id));
if ($format == 'lms') $PAGE->set_url($url);

//
// Print the page
//

// Print HTML title
if ($format == 'lms') $title = assessmentpath_report_print_activity_header($cm, $activity, $course);
else if ($format == 'html') $title = assessmentpath_report_print_activity_header_html($cm, $activity, $course, null, null, null, 'path-mod-assessmentpath-report');

// Prepare Excel title
$titles = array();

// KD2015-31 - End of "group members only" option
//$titles[] = get_string('groupresults_nostyle', 'scormlite', $grouping->name);
if (!is_null($grouping)) $titles[] = get_string('groupresults_nostyle', 'scormlite', $grouping->name);

$titles[] = $course->fullname;

//
// Fetch data
//

// Start workbook
$workbook = new assessmentpath_workbook($format, 'P3');
foreach ($ids as $id) {
	$cm = get_coursemodule_from_id('assessmentpath', $id, 0, false, MUST_EXIST);
	$activity = $DB->get_record('assessmentpath', array('id'=>$cm->instance), '*', MUST_EXIST);
	$activityid = $activity->id;
	$sheettitles = $titles;
	$sheettitles[] = format_string($activity->name);

	// Data
	$steps = array();
	$users = array();
	$scoids = assessmentpath_report_populate_steps($steps, $activityid);
	$userids = scormlite_report_populate_users($users, $courseid, $groupingid);
	if (empty($steps) || empty($users)) {
		echo '<p>'.get_string('noreportdata', 'scormlite').'</p>';
	} else {
		$global_avg = assessmentpath_report_populate_activity_results($steps, $users, $scoids, $userids);
	
		//
		// Build worksheet
		//
	
		// Start worksheet
		$worksheet = $workbook->add_worksheet($activity->code, $sheettitles, null, count($steps)+2);
		
		// Build table
		
		// Cols
		if ($format == 'lms') $cols = array('picture', 'fullname', $steps, 'avg');
		else $cols = array('fullname', $steps, 'avg');
		
		// Rank
		$config = get_config('assessmentpath');
		$displayrank = $config->displayrank;
		if ($displayrank) $cols[] = 'rank';
	
		// Table
		$table = new assessmentpath_report_table($courseid, $cols, $url);
		$table->define_presentation($activity->colors);
		$table->add_users($users, ($format == 'lms'));
		$table->add_average($steps, $global_avg);
		$worksheet->add_table($table);
		
		// Comments
		$commentform = new assessmentpath_comment_form();
		$content = $commentform->start($url, ($format == 'lms'));
		$content .= $commentform->addcomment($format, get_string("comments", "assessmentpath"), COMMENT_CONTEXT_GROUP_PATH, $activity->id);
		$content .= $commentform->finish();
		$worksheet->add_post_worksheet($content);
				
		// Display worksheet
		$worksheet->display();
		
		// Export buttons
		if ($format == 'lms') {
			$stepids = array_keys($steps);
			$stepids = implode(',', $stepids);
			$P4url = new moodle_url($CFG->wwwroot.'/mod/assessmentpath/report/P4.php', array('stepid'=>$stepids));
			scormlite_print_exportbuttons(array('html'=>$url, 'csv'=>$url, 'xls'=>$url, 'P4'=>$P4url));
		}
	}
}

// Close workbook
$workbook->close();


//
// Print footer
//

if ($format == 'lms') echo $OUTPUT->footer();
else if ($format == 'html') scormlite_print_footer_html();


?>
