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

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////
 
/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */ 
function assessmentpath_supports($feature) {
	switch($feature) {
		case FEATURE_MOD_ARCHETYPE:				return MOD_ARCHETYPE_OTHER;  // Type of module (resource, activity or assignment)
		case FEATURE_BACKUP_MOODLE2:			return true;  // True if module supports backup/restore of moodle2 format
		case FEATURE_GROUPS:					return false; // True if module supports groups
		case FEATURE_GROUPINGS:					return false; // True if module supports groupings
		case FEATURE_GROUPMEMBERSONLY:			return true;  // True if module supports groupmembersonly
		case FEATURE_SHOW_DESCRIPTION:			return true; // True if module can show description on course main page
		case FEATURE_NO_VIEW_LINK:				return false; // True if module has no 'view' page (like label)
		case FEATURE_MOD_INTRO:					return true;  // True if module supports intro editor
		case FEATURE_COMPLETION_TRACKS_VIEWS:	return true; // True if module has code to track whether somebody viewed it
		case FEATURE_COMPLETION_HAS_RULES:		return false; // True if module has custom completion rules
		case FEATURE_MODEDIT_DEFAULT_COMPLETION:return false; // True if module has default completion
		case FEATURE_GRADE_HAS_GRADE:			return false; // True if module can provide a grade
		// Next ones should be checked
		case FEATURE_GRADE_OUTCOMES:			return false; // True if module supports outcomes
		case FEATURE_ADVANCED_GRADING:			return false; // True if module supports advanced grading methods
		case FEATURE_IDNUMBER:					return false; // True if module supports outcomes
		case FEATURE_COMMENT:					return false; // 
		case FEATURE_RATE:						return false; //  
		default: return null;
	}
}
 
/**
 * Get icon mapping for font-awesome.
 * SF2017 - Added for 3.3 compatibility
 */
function mod_assessmentpath_get_fontawesome_icon_map() {
    return [
        'mod_assessmentpath:edit' => 'fa-pencil',
        'mod_assessmentpath:delete' => 'fa-close',
        'mod_assessmentpath:up' => 'fa-arrow-up',
        'mod_assessmentpath:down' => 'fa-arrow-down',
        'mod_assessmentpath:grades' => 'fa-table',
    ];
}

/**
 * Saves a new instance of the assessmentpath into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $data An object from the form in mod_form.php
 * @param mod_assessmentpath_mod_form $mform
 * @return int The id of the newly inserted assessmentpath record
 */  
function assessmentpath_add_instance($data, $form) { 
	global $DB, $CFG;
	require_once($CFG->dirroot.'/mod/assessmentpath/locallib.php');
	if (is_array($data->colors)) {
		$data->colors = implode(',', $data->colors);
	}
    
	$transaction = $DB->start_delegated_transaction();
	{
		$data->timemodified = time();
		$data->id = $DB->insert_record('assessmentpath', $data);
		foreach ($data->step_title as $key => $step_title) {
			if ($data->step_deleted[$key] != 1) {
				$step = new assessmentpath_step();
				$step->load_from_form($data, $form, $key);
				$step->save($data->coursemodule, $form, $key);
			}
		}
	}
	$DB->commit_delegated_transaction($transaction);
	
    // Grades
    $data->cmid = $data->coursemodule;
    if (!isset($data->cmidnumber)) $data->cmidnumber = '';
    assessmentpath_grade_item_update($data);
	
	return $data->id;
}

/**
 * Updates an instance of the assessmentpath in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $data An object from the form in mod_form.php
 * @param mod_assessmentpath_mod_form $mform
 * @return boolean Success/Fail
 */
function assessmentpath_update_instance($data, $form) {
	global $DB, $CFG;
	require_once($CFG->dirroot.'/mod/assessmentpath/report/reportlib.php');
	require_once($CFG->dirroot.'/mod/assessmentpath/locallib.php');
	if (is_array($data->colors)) {
		$data->colors = implode(',', $data->colors);
	}
	$transaction = $DB->start_delegated_transaction();
	{
		$data->timemodified = time();
		$data->id = $data->instance;
		$DB->update_record('assessmentpath', $data);
		$old_steps = $DB->get_records('assessmentpath_steps', array('activity'=>$data->id), '', 'id');
		foreach ($data->step_title as $key => $step_title) {
			if ($data->step_deleted[$key] != 1) {
				$step = new assessmentpath_step();
				$step->load_from_form($data, $form, $key);
				$step->save($data->coursemodule, $form, $key);
				unset($old_steps[$data->step_id[$key]]);
			}
		}
		if (!empty($old_steps)) {
			$steps_ids = implode(',', array_keys($old_steps));
			$sql = '
				DELETE S, T, SS, SST, C
				FROM {assessmentpath_steps} S
				LEFT JOIN {assessmentpath_tests} T ON T.step=S.id
				LEFT JOIN {scormlite_scoes} SS ON SS.id=T.sco
				LEFT JOIN {scormlite_scoes_track} SST ON SST.scoid=SS.id
				LEFT JOIN {assessmentpath_comments} C ON C.contextid=S.id AND C.contexttype='.COMMENT_CONTEXT_GROUP_STEP."
				WHERE S.id IN ($steps_ids)";
			$DB->execute($sql);
		}
	}
	$DB->commit_delegated_transaction($transaction);
	
	// Grades
    $data->cmid = $data->coursemodule;
    if (!isset($data->cmidnumber)) $data->cmidnumber = '';
    assessmentpath_grade_item_update($data);
    assessmentpath_update_grades($data);

	return true;
}

/**
 * Removes an instance of the assessmentpath from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function assessmentpath_delete_instance($id) {
	global $DB, $CFG;
	require_once($CFG->dirroot.'/mod/assessmentpath/report/reportlib.php');
	// Get activity information	
	$activity = $DB->get_record('assessmentpath', array('id'=>$id));
	if (! $activity) return false;
	// Delete in DB
	$sql = '
		DELETE A, S, T, SS, SST, C1, C2
		FROM {assessmentpath} A
		LEFT JOIN {assessmentpath_steps} S ON A.id=S.activity
		LEFT JOIN {assessmentpath_tests} T ON S.id=T.step
		LEFT JOIN {assessmentpath_comments} C1 ON C1.contexttype='.COMMENT_CONTEXT_GROUP_PATH.' AND C1.contextid=A.id
		LEFT JOIN {assessmentpath_comments} C2 ON C2.contexttype='.COMMENT_CONTEXT_GROUP_STEP.' AND C2.contextid=S.id
		LEFT JOIN {scormlite_scoes} SS ON SS.id=T.sco
		LEFT JOIN {scormlite_scoes_track} SST ON SST.scoid=SS.id
		WHERE A.id='.$id;
	$DB->execute($sql);
	
	// Grades
    assessmentpath_grade_item_delete($activity);
    
	return true;
}

// KD2015-02 - Remove _user_outline & _user_complete

/*
function assessmentpath_user_outline($course, $user, $mod, $moduleinstance) {
	return null;
}

function assessmentpath_user_complete($course, $user, $mod, $moduleinstance) {
	return true;
}
*/

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in assessmentpath activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function assessmentpath_print_recent_activity($course, $isteacher, $timestart) {
	return false;  //  True if anything was printed, otherwise false
}

/**
 * Returns all activity in assessmentpaths since a given time
 *
 * @param array $activities sequentially indexed array of objects
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid defaults to 0
 * @param int $groupid defaults to 0
 * @return void adds items into $activities and increases $index
 */
function assessmentpath_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see assessmentpath_get_recent_mod_activity()}

 * @return void
 */
function assessmentpath_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of assessmentpath. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $moduleinstanceid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function assessmentpath_get_participants($moduleinstanceid) {
	return false;
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $scormid id of scorm
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function assessmentpath_get_user_grades($activity, $userid=0) {
	global $CFG;
	require_once($CFG->dirroot.'/mod/assessmentpath/scormlitelib.php');
	$grades = array();
    if (empty($userid)) {
    	$raws = assessmentpath_get_grades($activity);
    	if (!empty($raws)) {
	        foreach ($raws as $userid => $raw) {
	            $grades[$userid] = new stdClass();
	            $grades[$userid]->id         = $userid;
	            $grades[$userid]->userid     = $userid;
	            $grades[$userid]->rawgrade   = $raw;
	        }    		
    	}
    } else {
    	$raw = assessmentpath_get_grade($userid, $activity);
    	if (isset($raw)) {
	        $grades[$userid] = new stdClass();
	        $grades[$userid]->id 		= $userid;
	        $grades[$userid]->userid 	= $userid;
	        $grades[$userid]->rawgrade 	= $raw;
    	}
    }
    if (empty($grades)) return false;
    return $grades;
}

/**
 * Creates or updates grade item for the give assessmentpath instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $assessmentpath instance object with extra cmidnumber and modname property
 * @return void
 */
function assessmentpath_grade_item_update($activity, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    $params = array('itemname'=>$activity->code);
    if (isset($activity->cmidnumber)) {
        $params['idnumber'] = $activity->cmidnumber;
    }
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = 100;
    $params['grademin']  = 0;
    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/assessmentpath', $activity->course, 'mod', 'assessmentpath', $activity->id, 0, $grades, $params);
}

/**
 * Update assessmentpath grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $assessmentpath instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function assessmentpath_update_grades($activity, $userid=0, $nullifnone=true) {
	global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    if ($grades = assessmentpath_get_user_grades($activity, $userid)) {
    	assessmentpath_grade_item_update($activity, $grades);
    } else if ($userid and $nullifnone) {
    	$grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        assessmentpath_grade_item_update($activity, $grade);
    } else {
    	assessmentpath_grade_item_update($activity);
    }
}

/**
 * Delete grade item for given scorm
 *
 * @global stdClass
 * @param object $scorm object
 * @return object grade_item
 */
function assessmentpath_grade_item_delete($activity) {
	global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    return grade_update('mod/assessmentpath', $activity->course, 'mod', 'assessmentpath', $activity->id, 0, null, array('deleted'=>1));
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function assessmentpath_get_file_areas($course, $cm, $context) {
	global $DB;
	$steps = $DB->get_records('assessmentpath_steps', array('activity' => $cm->instance), 'rank ASC', 'id,title,code');
	$areas = array();
	require_once(dirname(__FILE__).'/locallib.php');
	foreach ($steps as $step) {
		$areas[STEP_FILEAREA.$step->id] = get_string('step_filearea', 'assessmentpath', $step);
	}
	return $areas;
}

/**
 * File browsing support for SCORM file areas
 *
 * @param stdclass $browser
 * @param stdclass $areas
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return stdclass file_info instance or null if not found
 */
function assessmentpath_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {

	global $CFG, $DB;

	$file_info = null;
	
	if (has_capability('moodle/course:managefiles', $context)) {

		$fs = get_file_storage();
		$filepath = is_null($filepath) ? '/' : $filepath;
		$filename = is_null($filename) ? '.' : $filename;
		$urlbase = $CFG->wwwroot.'/pluginfile.php';

		if (strpos($filearea, STEP_FILEAREA) !== false) {
			// Step directory
			$stepid = intval(substr($filearea, strlen(STEP_FILEAREA)));
			$storedfile = new virtual_root_file($context->id, 'mod_assessmentpath', $filearea, $stepid);
			require_once(dirname(__FILE__).'/locallib.php');
			$file_info = new assessmentpath_step_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea]);

		} else if ($filearea === TEST_FILEAREA) {
			// Test directory
			$storedfile = new virtual_root_file($context->id, 'mod_assessmentpath', $filearea, $itemid);
			require_once(dirname(__FILE__).'/locallib.php');
			$test = $DB->get_record('assessmentpath_tests', array('id' => $itemid), '*', MUST_EXIST);
			$file_info = new assessmentpath_test_file_info($browser, $context, $storedfile, $urlbase, $test);

		} else {
			$storedfile = $fs->get_file($context->id, 'mod_assessmentpath', $filearea, $itemid, $filepath, $filename);
			if ($storedfile) {
				$sql = '
					SELECT T.*
					FROM {assessmentpath_tests} T
					INNER JOIN {scormlite_scoes} SS ON SS.id=T.sco
					WHERE T.sco=?';
				$test = $DB->get_record_sql($sql, array($itemid), MUST_EXIST);
				if ($filearea === SCO_CONTENT_FILEAREA) {
					require_once($CFG->dirroot.'/mod/scormlite/locallib.php');
					$file_info = new assessmentpath_test_content_file_info($browser, $context, $storedfile, $urlbase, $test);
				
				} else if ($filearea === SCO_PACKAGE_FILEAREA) {
					$file_info = new assessmentpath_test_package_file_info($browser, $context, $storedfile, $urlbase, $test);
				}
			}
		}
	}
	return $file_info;
}

/**
 * Serves the files from the assessmentpath file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return void this should never return to the caller
 */
function assessmentpath_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');
	return scormlite_shared_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options, 'assessmentpath');
}
	
////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding assessmentpath nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the assessmentpath module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function assessmentpath_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the assessmentpath settings
 *
 * This function is called when the context for the page is a assessmentpath module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $assessmentpathnode {@link navigation_node}
 */
function assessmentpath_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $assessmentpathnode=null) {
}

////////////////////////////////////////////////////////////////////////////////
// Reset feature                                                             //
////////////////////////////////////////////////////////////////////////////////


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the scorm.
 *
 * @param object $mform form passed by reference
 */
function assessmentpath_reset_course_form_definition(&$mform) {
	$mform->addElement('header', 'scormheader', get_string('modulenameplural', 'assessmentpath'));
	$mform->addElement('advcheckbox', 'reset_assessmentpath', get_string('delete_tracks_and_comments', 'assessmentpath'));
}

/**
 * Course reset form defaults.
 *
 * @return array
 */
function assessmentpath_reset_course_form_defaults($course) {
	return array('reset_assessmentpath'=>1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function assessmentpath_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT s.*, cm.idnumber as cmidnumber, s.course as courseid
              FROM {assessmentpath} s, {course_modules} cm, {modules} m
             WHERE m.name='assessmentpath' AND m.id=cm.module AND cm.instance=s.id AND s.course=?";

    if ($activities = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($activities as $activity) {
            assessmentpath_grade_item_update($activity, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * scorm attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function assessmentpath_reset_userdata($data) {
	$status = array();
	if (!empty($data->reset_assessmentpath)) {

		global $CFG;
		require_once($CFG->dirroot . '/mod/assessmentpath/report/reportlib.php');

		// SCORM Lite tracks
		$sql = '
			DELETE SST
			FROM {scormlite_scoes_track} SST
			INNER JOIN {assessmentpath_tests} T ON T.sco=SST.scoid
			INNER JOIN {assessmentpath_steps} S ON S.id=T.step
			INNER JOIN {assessmentpath} A ON A.id=S.activity
			INNER JOIN {course_modules} CM ON CM.instance=A.id
			WHERE CM.course=?';
		global $DB;
		$DB->execute($sql, array($data->courseid));

		// Activity comments
		$sql = '
			DELETE COMMENT
			FROM {assessmentpath_comments} COMMENT
			INNER JOIN {assessmentpath} A ON A.id=COMMENT.contextid
			INNER JOIN {course_modules} CM ON CM.instance=A.id
			WHERE CM.course=? AND COMMENT.contexttype = ' . COMMENT_CONTEXT_USER_PATH;
		global $DB;
		$DB->execute($sql, array($data->courseid));

		// Course comments
		$sql = '
			DELETE COMMENT
			FROM {assessmentpath_comments} COMMENT
			WHERE COMMENT.contextid=? AND COMMENT.contexttype = ' . COMMENT_CONTEXT_USER_COURSE;
		global $DB;
		$DB->execute($sql, array($data->courseid));

		// Grades
        if (empty($data->reset_gradebook_grades)) {
            assessmentpath_reset_gradebook($data->courseid);
		}
		
		// Status
		$status[] = array(
			'component' => get_string('modulenameplural', 'assessmentpath'),
			'item' => get_string('delete_tracks_and_comments', 'assessmentpath'),
			'error' => false);
	}
	return $status;
}

////////////////////////////////////////////////////////////////////////////////
// Additional stuff (not in the template)                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function assessmentpath_page_type_list($pagetype, $parentcontext, $currentcontext) {
	$module_pagetype = array('mod-assessmentpath-*'=>get_string('page-mod-assessmentpath-x', 'assessmentpath'));
	return $module_pagetype;
}

/**
 * writes overview info for course_overview block - displays upcoming scorm objects that have a due date
 *
 * @param object $type - type of log(aicc,scorm12,scorm13) used as prefix for filename
 * @param array $htmlarray
 * @return mixed
 */
function assessmentpath_print_overview($courses, &$htmlarray) {
}
