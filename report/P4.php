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
$stepids = required_param('stepid', PARAM_TEXT);
$format  = optional_param('format', 'lms', PARAM_ALPHA);  // 'lms', 'csv', 'html', 'xls'
$action  = optional_param('action', '', PARAM_ALPHA);  // 'save', 'edit'

// Step List
$stepids = explode(',', $stepids);
$stepid = $stepids[0];

// Useful objects and vars
$step = $DB->get_record('assessmentpath_steps', array('id' => $stepid), '*', MUST_EXIST);
$activity = $DB->get_record('assessmentpath', array('id' => $step->activity), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('assessmentpath', $activity->id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$courseid = $course->id;
$activityid = $activity->id;
$stepid = $step->id;

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
if ($action == 'edit' && !has_capability('mod/scormlite:modifyscores', $context)) $action = '';
$url = new moodle_url('/mod/assessmentpath/report/P4.php', array('stepid'=>$stepid));
if ($format == 'lms') $PAGE->set_url($url);

//
// Print the play page
//

// Print HTML title
if ($format == 'lms') $titlelink = assessmentpath_report_get_link_P3($cm->id);
else $titlelink = null;
$subtitle = '['.$step->code.'] '.$step->title;
if ($format == 'lms') $title = assessmentpath_report_print_activity_header($cm, $activity, $course, $titlelink, $subtitle);
else if ($format == 'html') $title = assessmentpath_report_print_activity_header_html($cm, $activity, $course, $titlelink, $subtitle, null, 'path-mod-assessmentpath-report');

// Prepare Excel title
$titles = array();

// KD2015-31 - End of "group members only" option
// $titles[] = get_string('groupresults_nostyle', 'scormlite', $grouping->name);
if (!is_null($grouping)) $titles[] = get_string('groupresults_nostyle', 'scormlite', $grouping->name);

$titles[] = $course->fullname;
$titles[] = format_string($activity->name);

//
// Save data
//

if ($action == 'save') {
	$initial = array();
	$remediation = array();
	foreach($_POST as $id => $val) {
		$exp = explode('_', $id);		
		if ($exp[0] == 'scorefield') {
			$val = intval($val);
			if (!empty($val) && $val <= 100) {
				if (count($exp) == 2) {
					$initial[$exp[1]] = $val;
				} else if (count($exp) == 3) {
					$remediation[$exp[2]] = $val;
				}				
			}				
		}			
	}
	if (!empty($initial)) assessmentpath_set_step_users_scores($initial, $stepid, 0);
	if (!empty($remediation)) assessmentpath_set_step_users_scores($remediation, $stepid, 1);
}

//
// Fetch data
//

// Start workbook
$workbook = new assessmentpath_workbook($format, 'P4');
foreach ($stepids as $stepid) {
	$step = $DB->get_record('assessmentpath_steps', array('id' => $stepid), '*', MUST_EXIST);
	$sheettitles = $titles;
	$sheettitles[] = format_string($step->title);

	// Data
	$users = array();
	$scoids = assessmentpath_report_populate_step($step);
    $userids = scormlite_report_populate_users($users, $courseid, $groupingid);	
	if (empty($users)) {
		echo '<p>'.get_string('noreportdata', 'scormlite').'</p>';
	} else {
		$statistics = assessmentpath_report_populate_step_results($step, $users, $scoids, $userids);
	
		//
		// Build worksheet
		//
	
		// Start worksheet
		$worksheet = $workbook->add_worksheet($step->code, $sheettitles, array(40, 20));
		
		// Modification Form start
		if ($format == 'lms') {
			if ($action == 'edit') {
				$editurl = new moodle_url($url, array('action'=>'save'));
				$content = '<form action="'.$editurl.'" method="post">';
			} else {
				$editurl = new moodle_url($url, array('action'=>'edit'));
				$content = '<form action="'.$editurl.'" method="post">';
			}
			$worksheet->add_pre_worksheet($content);
		}
		
		// Main table
		
		// Cols
		$cols = array();
		if ($format == 'lms') $cols[] = 'picture';
		$cols[] = 'fullname';
		$cols[] = 'initialscore';
		if ($format == 'lms' && $action == 'edit') $cols[] = 'scorefield';
		$config = get_config('assessmentpath');
		$displayrank = $config->displayrank;
		if ($displayrank) $cols[] = 'rank';
		$cols[] = 'remediationscore';
		if ($format == 'lms' && $action == 'edit') $cols[] = 'scorefield_R';
		// Table
		$table = new assessmentpath_report_table($courseid, $cols, $url, $step);
		$table->define_presentation($activity->colors);
		$table->add_users($users, ($format == 'lms'));
		$worksheet->add_table($table);
		
		// Modification Form end
		if ($format == 'lms') {
			if ($action == 'edit') {
				$content = '<div class="buttons"><input type="submit" class="btn btn-primary" value="'.get_string('scoresubmit', 'assessmentpath').'"/></div>';		
				$content .= '</form>';		
			} else {
				$content = '<div class="buttons"><input type="submit" class="btn btn-default" value="'.get_string('scoreedit', 'assessmentpath').'"/></div>';		
				$content .= '</form>';		
			}
			$worksheet->add_comment($content);
		}
		
		// Statistics table
		if ($format != 'csv') {
			$table = new assessmentpath_report_table($courseid, array('title', 'beforeremediation', 'afterremediation'), $url);
			$table->define_presentation($activity->colors);
			$table->add_scores(get_string('remediationaverage', 'assessmentpath'), array($statistics->avg_remediation_beforeremediation, $statistics->avg_remediation_afterremediation));
			$table->add_scores(get_string('groupaverage', 'scormlite'), array($statistics->avg_group_beforeremediation, $statistics->avg_group_afterremediation));
			$worksheet->add_table($table);
		}
		
		// Comments
		$commentform = new assessmentpath_comment_form();
		$content = $commentform->start($url, ($format == 'lms'));
		$content .= $commentform->addcomment($format, get_string("comments", "assessmentpath"), COMMENT_CONTEXT_GROUP_STEP, $stepid);
		$content .= $commentform->finish();
		$worksheet->add_post_worksheet($content);
			
		// Display worksheet
		$worksheet->display();
		
		// Export buttons
		if ($format == 'lms') {
			scormlite_print_exportbuttons(array('html'=>$url, 'csv'=>$url, 'xls'=>$url));
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
