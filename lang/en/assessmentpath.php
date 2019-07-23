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
 * Strings for component 'assessmentpath', language 'en'
 *
 */

// Plugin strings

$string['assessmentpath'] = 'Assessment Path';
$string['modulename'] = 'Assessment Path';
$string['modulename_help'] = 'The Assessment Path activity enables to create a sequence of inital and remedial tests.';
$string['modulenameplural'] = 'Assessment Paths';
$string['pluginadministration'] = 'Assessment Path';
$string['pluginname'] = 'Assessment Path';
$string['page-mod-assessmentpath-x'] = 'Any Assessment Path module page';

// Permissions

$string['assessmentpath:addinstance'] = 'Add a new Assessment Path';
$string['assessmentpath:notifycompletion'] = 'Receive completion notifications';

// Edit page

// Tabs layout
$string['maintab'] = 'Main settings';
$string['stepstab'] = 'Steps editing';
$string['step'] = 'Step';
$string['steps'] = 'Steps';
$string['test'] = 'Test';
$string['tests'] = 'Tests';
$string['initial'] = 'Initial';
$string['remediation'] = 'Remedial';
$string['initialtest'] = 'Initial test';
$string['remediationtest'] = 'Remedial test';
// Step commands
$string['deletestep'] = 'Delete';
$string['moveupstep'] = 'Move up';
$string['movedownstep'] = 'Move down';
$string['advancedstep'] = 'Advanced settings';
$string['insertstep'] = 'Insert a new step';

// Reports

// Report titles
$string['P0'] = 'Progress';
$string['P1'] = 'Individual results';
$string['MyP1'] = 'My results';
$string['P2'] = 'Global group results';
$string['P3'] = 'Group results per assessment path';
$string['P4'] = 'Group results per test';
// Export buttons
$string['exportbookP1'] = 'Export users (Excel)';
$string['exportbookP3'] = 'Export paths (Excel)';
$string['exportbookP4'] = 'Export details (Excel)';
// Useful terms
$string['beforeremediation'] = 'Before remedial';
$string['afterremediation'] = 'After remedial';
$string['remediationaverage'] = 'Average score for students in remedial';
$string['path'] = 'Path';
$string['progress'] = 'Assessment path progress';
// Score modification
$string['scorefield'] = 'Edit';
$string['scorefield_R'] = 'Edit';
$string['scoreedit'] = 'Modify scores';
$string['scoresubmit'] = 'Save new scores';
// Statistics
$string['back'] = 'Back';
$string['statistics'] = 'Quetzal Statistics';


// Comments

$string['savecomments'] = 'Save comments';
$string['comments'] = 'Comments';
$string['testcomments'] = 'Test comments';
$string['pathcomments'] = 'Path comments';
$string['coursecomments'] = 'Course comments';


// File info browser

$string['step_filearea'] = '{$a->code}';
$string['test_initial_filearea'] = 'Initial';
$string['test_remediation_filearea'] = 'Remedial';


// Notifications

$string['emailnotifybody'] = 'Hi {$a->username},

{$a->studentname} has completed the Assessment Path activity named \'{$a->activityname}\' ({$a->activityurl}) in course \'{$a->coursename}\'.

You can review this activity here: {$a->activityreporturl}.';
$string['emailnotifysmall'] = '{$a->studentname} has completed {$a->activityname}. See {$a->activityreporturl}';
$string['emailnotifysubject'] = '{$a->studentname} has completed {$a->activityname}';

$string['messageprovider:completion'] = 'Assessment Path completion';
$string['crontask_notifications_queue'] = 'Assessment Path notifications queue';


// Privacy metadata
$string['privacy:metadata:scormlite_scoes_track'] = 'Data tracked by the SCORM Lite activities';
$string['privacy:metadata:scoes_track:userid'] = 'The ID of the user who accessed the SCORM Lite activity';
$string['privacy:metadata:scoes_track:attempt'] = 'The attempt number';
$string['privacy:metadata:scoes_track:element'] = 'The name of the element to be tracked';
$string['privacy:metadata:scoes_track:value'] = 'The value of the given element';
$string['privacy:metadata:scoes_track:timemodified'] = 'The time when the tracked element was last modified';
