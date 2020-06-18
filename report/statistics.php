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
$scoid = required_param('scoid', PARAM_INT); 
$stepid = required_param('stepid', PARAM_INT); 
$groupingid = optional_param('groupingid', null, PARAM_INT);

// Useful objects and vars
$step = $DB->get_record('assessmentpath_steps', array('id' => $stepid), '*', MUST_EXIST);
$activity = $DB->get_record('assessmentpath', array('id' => $step->activity), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('assessmentpath', $activity->id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$sco = $DB->get_record("scormlite_scoes", array("id"=>$scoid), '*', MUST_EXIST);

// Page URL
$url = new moodle_url('/mod/assessmentpath/report/statistics.php', array('scoid'=>$scoid, 'stepid'=>$stepid, 'groupingid'=>$groupingid));
$PAGE->set_url($url);

// Check permissions
$context_course = context_course::instance($course->id);
require_login($course, false, $cm);
require_capability('mod/scormlite:viewotherreport', $context_course);

//
// Print the page
//

// Start
scormlite_print_header($cm, $activity, $course);

// Tabs
$playurl = "$CFG->wwwroot/mod/assessmentpath/view.php?id=$cm->id";
$reporturl = "$CFG->wwwroot/mod/assessmentpath/report/P3.php?id=$cm->id";
scormlite_print_tabs($cm, $activity, $playurl, $reporturl, 'report');

// Title and description
echo '<h2>'.get_string('statistics', 'assessmentpath').'</h2>';


// ----------- Relevant data ---------

// Print the stats
scormlite_report_print_quetzal_statistics($scoid);


// ----------- Commands ---------

$backUrl = "$CFG->wwwroot/mod/assessmentpath/report/P4.php?stepid=$stepid&groupingid=$groupingid";
echo '<a href="'.$backUrl.'" class="btn btn-default">'.get_string('back', 'assessmentpath').'</a>';

// End
echo $OUTPUT->footer();

?>


