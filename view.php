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
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/assessmentpath/locallib.php');
require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');

global $PAGE, $OUTPUT, $USER;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('assessmentpath', $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", array("id"=>$cm->course), '*', MUST_EXIST);
$activity = $DB->get_record('assessmentpath', array('id'=>$cm->instance), '*', MUST_EXIST);
$steps = assessmentpath_get_steps($cm->instance);

//
// Page setup 
//

require_login($course->id, false, $cm);
$url = new moodle_url('/mod/assessmentpath/view.php', array('id'=>$id));
$PAGE->set_url($url);

//
// Logs
//

assessmentpath_trigger_path_event('course_module_viewed', $course, $cm, $activity);

//
// Print the play page
//

// Start
scormlite_print_header($cm, $activity, $course);

// Tabs
$playurl = "$CFG->wwwroot/mod/assessmentpath/view.php?id=$cm->id";
$reporturl = "$CFG->wwwroot/mod/assessmentpath/report/P3.php?id=$cm->id";
scormlite_print_tabs($cm, $activity, $playurl, $reporturl, 'play');

// Title and description
scormlite_print_title($cm, $activity);
scormlite_print_description($cm, $activity);

// My status box
echo $OUTPUT->box_start('generalbox mdl-align myprofile');
scormlite_print_myprofile($cm);
echo $OUTPUT->box_end();

// Steps
echo '<div id="steps">';
$i = 0;
foreach ($steps as $step) {
	$html = '';
	$stepclosed = true;
	$stepstart = false;
	foreach ($step->tests as $test) {
		// Data
		$res = scormlite_get_mystatus($cm, $test->scodata, true);
		$html_mystatus = $res[0];
		$trackdata = $res[1];
		if (!$test->data->remediation) {
			$prectest = $test;
			$prectrackdata = $trackdata;
			$res = scormlite_get_availability($cm, $test->scodata, $trackdata);
			$html_availability = $res[0];
			$scormopen = $res[1];
		} else {
			$autoavailable = ($prectrackdata->status == 'failed' && $prectrackdata->attemptnb == $prectest->scodata->maxattempt);
			$res = scormlite_get_availability($cm, $test->scodata, $trackdata, $autoavailable);
			$html_availability = $res[0];
			$scormopen = $res[1];
		} 
		$res = scormlite_get_myactions($cm, $test->scodata, $trackdata, $scormopen);
		$html_myactions = $res[0];
		$action = $res[1];
		// For CSS
		$stepclosed = $stepclosed && empty($action);
		$stepstart = $stepstart || ($action == "start");
		if ($action == "start" && $test->data->remediation) $rem = "start";
		else $rem = "";
		// HTML
		$html_test = '';
		if ($test->data->remediation) {
			$html_test .= '<div class="sep '.$rem.'"></div>';
		}
		if (!$test->data->remediation) {
			$html_test .= '<div class="initialtest testsettings '.$trackdata->status.'">
				<div class="testinner">
					<h5>'.get_string('initialtest','assessmentpath').'</h5>
			';
		} else {
			$html_test .= '<div class="remediationtest testsettings '.$trackdata->status.'">
 				<div class="testinner">
					<h5>'.get_string('remediationtest','assessmentpath').'</h5>
			';
		} 
		$html_test .= $html_mystatus.$html_availability.$html_myactions;
		$html_test .= '</div></div>';
		$html .= $html_test;
	}
	// For CSS
	$status = '';
	if ($stepclosed) $status = 'closed';
	if ($stepstart) $status = 'start';
	//
	echo '
	<div class="step '.$status.'" id="step'.$i.'">
		<div class="step-inner">
			<div class="stepsettings">
				<h4><span>['.$step->data->code.'] '.$step->data->title.'</span></h4>
			</div>
			<div class="steptests">';
	echo $html;
	echo '				
			</div>
		</div>
	</div>
	';
	$i += 1;
}
echo '</div>';

//
// The end
//

echo $OUTPUT->footer();

?>