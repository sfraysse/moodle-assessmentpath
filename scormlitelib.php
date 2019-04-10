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

// Each module using scormlite must provide a file such as this one, providing the following functions

function assessmentpath_get_activity_from_scoid($scoid) {
	global $DB;
	$sql = "
		SELECT A.*
		FROM {assessmentpath} A
		INNER JOIN {assessmentpath_steps} S ON S.activity=A.id
		INNER JOIN {assessmentpath_tests} T ON S.id=T.step AND T.sco=".$scoid;
	return $DB->get_record_sql($sql, array(), MUST_EXIST);
}

// Returns the activity completion

function assessmentpath_is_activity_completed($userid, $activity) {
	global $CFG, $DB;
	require_once($CFG->dirroot.'/mod/assessmentpath/report/reportlib.php');

	// Initial
	$steps = array();
	$scoids = assessmentpath_report_populate_steps($steps, $activity->id);
	$scores = assessmentpath_report_get_scores($scoids, array($userid), false);

	// Remedial
	$steps_remed = array();
	$scoids_remed = assessmentpath_report_populate_steps($steps_remed, $activity->id, 1);
	$scores_remed = assessmentpath_report_get_scores($scoids_remed, array($userid), false);

	// No score at all for this user (check initial)
	if (!array_key_exists($userid, $scores)) return false;

	// Check scores for each step
	foreach($steps as $stepid => $step) {

		// No score for this step
		if (!array_key_exists($step->scoid, $scores[$userid])) return false;

		// Check initial
		$tracks = scormlite_get_tracks($step->scoid, $userid);
		if (empty($tracks)) return false;
		$sco = $DB->get_record("scormlite_scoes", array("id" => $step->scoid), '*', MUST_EXIST);
		if (!assessmentpath_is_test_completed($sco, $tracks)) return false;

		// Success: no remedial
		if ($tracks->success_status == "passed") continue;

		// No remedial in the path
		if (!isset($steps_remed[$stepid])) continue;

		// Check remedial
		$step_remed = $steps_remed[$stepid];
		$tracks = scormlite_get_tracks($step_remed->scoid, $userid);
		if (empty($tracks)) return false;
		$sco = $DB->get_record("scormlite_scoes", array("id" => $step_remed->scoid), '*', MUST_EXIST);
		if (!assessmentpath_is_test_completed($sco, $tracks)) return false;
	}
	return true;
}

// Returns the test completion

function assessmentpath_is_test_completed($sco, $tracks)
{
	if ($sco->review_access < 2) {

		// Default completion tracking
		return $tracks->success_status == "passed"
			|| $tracks->success_status == "failed"
			|| $tracks->completion_status == "completed";
	} else {

		// Special rule
		return $tracks->success_status == "passed"
			|| $tracks->attemptnb == $sco->maxattempt;
	}
}

// Returns the user grade for this activity or NULL if there is no grade to record

function assessmentpath_get_grade($userid, $activity) {
	global $CFG;
	require_once($CFG->dirroot.'/mod/assessmentpath/report/reportlib.php');
	$steps = array();
	$scoids = assessmentpath_report_populate_steps($steps, $activity->id);
	$scores = assessmentpath_report_get_scores($scoids, array($userid), false);
	$completed = true;
	$foravg = array();
	foreach($steps as $stepid => $step) {
		if (array_key_exists($userid, $scores) && array_key_exists($step->scoid, $scores[$userid])) {
			// There is a score
			$foravg[] = $scores[$userid][$step->scoid];
		} else {
			// No score, not completed
			$completed = false;
			break;
		}		
	}
	if ($completed && !empty($foravg)) {
		$avg = array_sum($foravg) / count($foravg);
		return $avg; 
	}
}

// Returns the grades for this activity

function assessmentpath_get_grades($activity) {
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
	require_once($CFG->dirroot.'/mod/assessmentpath/report/reportlib.php');
	$cm = get_coursemodule_from_instance('assessmentpath', $activity->id, $activity->course);
	if (empty($cm->groupingid)) {
		// No grouping, no reporting!
		return array();	
	} else {
		// Grouping, go
		$res = array();
		$steps = array();
		$users = array();
		$scoids = assessmentpath_report_populate_steps($steps, $activity->id);
        
        // KD2015-31 - End of "group members only" option
		// $userids = scormlite_report_populate_users($users, $activity->course, $cm->groupingid);
        global $DB;
        $course = $DB->get_record('course', array('id'=>$activity->course), '*', MUST_EXIST);
		$userids = scormlite_report_populate_users($users, $course, $cm);
        
		assessmentpath_report_populate_activity_results($steps, $users, $scoids, $userids, false);
		foreach($users as $userid => $user) {
			$res[$userid] = $user->avg;
		}	
		return $res;		
	}
}


