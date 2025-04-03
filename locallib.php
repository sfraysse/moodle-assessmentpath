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

//
// Steps list
//

// Get a list of step objects by activity id

function assessmentpath_get_steps($activityid) {
	global $DB;
	$datas = $DB->get_records('assessmentpath_steps', array('activity'=>$activityid), 'position ASC');
	$steps = array();
	foreach ($datas as $r) {
		$steps[$r->id] = new assessmentpath_step();
		$steps[$r->id]->load_from_db($r);
	}
	return $steps;
}

//
// Step class
//

class assessmentpath_step {
	
	public $data = null;  // Object for DB
	public $tests = array();    // 1st test is the initial, 2nd is the remediation

	// Load data from DB
	
	public function load_from_db($record) {
		// For the DB
		$this->data = $record;
		// Initial test
		$test_initial = new assessmentpath_test();
		$test_initial->load_from_db($this->data->id, 0);
		$this->tests[] = &$test_initial;
		// Remediation test
		$test_remediation = new assessmentpath_test();
		if ($test_remediation->load_from_db($this->data->id, 1)) {
			$this->tests[] = &$test_remediation;
		}
	}

	// Load data from the form
	
	public function load_from_form($formdata, $form, $index) {
		// For the DB
		$this->data = new stdClass();
		$this->data->id = $formdata->step_id[$index];
		$this->data->title = $formdata->step_title[$index];
		$this->data->code = $formdata->step_code[$index];
		$this->data->activity = $formdata->id;
		$this->data->position = $formdata->step_rank[$index];
		// Initial test
		$test_initial = new assessmentpath_test();
		$test_initial->load_from_form($formdata, $form, $index, false);
		$this->tests[] = &$test_initial;
		// Remediation test
		$test_remediation = new assessmentpath_test();
		$test_remediation->load_from_form($formdata, $form, $index, true);
		$this->tests[] = &$test_remediation;
	}

	// Save the step
	
	public function save($cmid, $form, $index) {
		global $DB;
		// DB update
		if ($this->data->id != 0) $DB->update_record('assessmentpath_steps', $this->data); // Updating
		else $this->data->id = $DB->insert_record('assessmentpath_steps', $this->data); // New step
		// Save the tests
		foreach ($this->tests as $test) {
			$test->save($cmid, $form, $index, $this->data->id);
		}
	}
}

//
// Test class
//

class assessmentpath_test {

	public $data = null;  // Object for DB
	public $scodata = null;  // SCO object for DB
	
	// Load data from the DB
	
	public function load_from_db($stepid, $remediation) {
		global $DB;
        $records = $DB->get_records("assessmentpath_tests", array("step"=>$stepid, "remediation"=>$remediation));
		if (empty($records)) return false;
        foreach($records as $record) {
            $this->data = $record;
            break;
        }
        $this->scodata = $DB->get_record("scormlite_scoes", array("id"=>$this->data->sco), '*', MUST_EXIST);
        return true;
	}

	// Load data from the form
	
	public function load_from_form($formdata, $form, $index, $is_remediation) {

		// Load SCO props
		$prefix = self::Form_Prefix($is_remediation, true);
		$this->scodata = new stdClass();
		$sco_properties = array('id', 'containertype', 'scormtype', 'reference', 'sha1hash', 'revision', 'manualopen', 'maxtime', 'passingscore', 'maxattempt', 'whatgrade', 'lock_attempts_after_success', 'popup', 'displaychrono', 'review_access');
		foreach ($sco_properties as $prop) {
			if (isset($formdata->{$prefix.$prop}[$index])) {
				$this->scodata->{$prop} = $formdata->{$prefix.$prop}[$index];
			}
		}
		
		// Timeopen
		$propname = $prefix.'timeopen';
		$propval = $formdata->$propname;
		$propval = $propval[$index];
		$this->scodata->timeopen = $propval;

		// Timeclose
		$propname = $prefix.'timeclose';
		$propval = $formdata->$propname;
		$propval = $propval[$index];
		$this->scodata->timeclose = $propval;

		// Colors
		$this->scodata->colors = $formdata->colors;

		// Load test props
		$prefix = self::Form_Prefix($is_remediation, false);
		$this->data = new stdClass();
		$this->data->id = $formdata->{"{$prefix}id"}[$index];
		$this->data->step = $formdata->step_id[$index];		// Step ID
		$this->data->sco = $this->scodata->id;				// SCO ID
		$this->data->remediation = $is_remediation;
	}

	// Save the test
	
	public function save($cmid, $form, $index, $stepid) {
		global $DB, $CFG;
		require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');
		// Update step ID
		$this->data->step = $stepid; 
		// Save the SCO
		$prefix = self::Form_Prefix($this->data->remediation, true);
		$file_fieldname = "{$prefix}packagefile_{$index}";
		if ($this->data->sco = scormlite_save_sco($this->scodata, $form, $cmid, $file_fieldname, true)) {
			// DB update
			if ($this->data->id != 0) $DB->update_record('assessmentpath_tests', $this->data); // Updating
			else $this->data->id = $DB->insert_record('assessmentpath_tests', $this->data); // New step
		}
	}

	// Return the form element prefix for a test
	 
	public static function Form_Prefix($is_remediation, $sco_prefix) {
		$prefix = 'test_';
		if ($is_remediation) $prefix .= 'remediation_';
		else $prefix .= 'init_';
		if ($sco_prefix) $prefix.= 'sco_';
		return $prefix;
	}

}

//
// File browsing
//

define('STEP_FILEAREA', 'assessmentpath_step');
define('TEST_FILEAREA', 'assessmentpath_test');
define('SCO_PACKAGE_FILEAREA', 'package');
define('SCO_CONTENT_FILEAREA', 'content');

global $CFG;

require_once($CFG->libdir.'/filelib.php');


/**
 * file_info implementation for browsing a Step as a directory of Tests.
 */
class assessmentpath_step_file_info extends file_info_stored {

	private $stepid;

	public function __construct($browser, $context, $storedfile, $urlbase, $topvisiblename) {
		parent::__construct($browser, $context, $storedfile, $urlbase, $topvisiblename, false, true, false, false);
	}

	public function get_children() {
		global $DB;
		$tests = $DB->get_records('assessmentpath_tests', array('step' => $this->lf->get_itemid()), 'remediation ASC');
		$children = array();
		foreach ($tests as $test) {
			$file = new virtual_root_file($this->context->id, 'mod_assessmentpath', TEST_FILEAREA, $test->id);
			$children[] = new assessmentpath_test_file_info($this->browser, $this->context, $file, $this->urlbase, $test);
		}
		return $children;
	}

	public function is_empty_area() {
		// Assuming that a step contains always at least one sco
		return false;
	}
}

/**
 * file_info implementation for browsing a Test as a scormlite activity (package + content).
 */
class assessmentpath_test_file_info extends file_info_stored {

	private $test;

	public function __construct($browser, $context, $storedfile, $urlbase, $test) {
		$keyname = $test->remediation ? 'test_remediation_filearea' : 'test_initial_filearea';
		$name = get_string($keyname, 'assessmentpath', $test);
		parent::__construct($browser, $context, $storedfile, $urlbase, $name, true, true, false, false);
		$this->test = $test;
	}

	public function get_parent() {
		if ($this->lf->get_filepath() === '/' and $this->lf->is_directory()) {
			return $this->browser->get_file_info($this->context, $this->lf->get_component(), STEP_FILEAREA.$this->test->step);
		}
		return parent::get_parent();
	}

	public function get_children() {
		$children = array(
			new assessmentpath_test_package_file_info(
				$this->browser, $this->context,
				new virtual_root_file($this->context->id, 'mod_assessmentpath', SCO_PACKAGE_FILEAREA, $this->test->sco),
				$this->urlbase, $this->test),
			new assessmentpath_test_content_file_info(
				$this->browser, $this->context,
				new virtual_root_file($this->context->id, 'mod_assessmentpath', SCO_CONTENT_FILEAREA, $this->test->sco),
				$this->urlbase, $this->test)
		);
		return $children;
	}
	

	public function is_empty_area() {
		// Assuming that a step contains always at least one sco
		return false;
	}
}

/**
 * Base file_info implementation for the "Package" and "Content" directories of a Test.
 */
class assessmentpath_test_basecontent_file_info extends file_info_stored {
	private $test;

	public function __construct(file_browser $browser, $context, $storedfile, $urlbase, $name, $test) {
		parent::__construct($browser, $context, $storedfile, $urlbase, $name, true, true, false, false);
		$this->test = $test;
	}

	public function get_parent() {
		if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
			return $this->browser->get_file_info($this->context, $this->lf->get_component(), TEST_FILEAREA, $this->test->id);
		}
		return parent::get_parent();
	}

	public function is_empty_area() {
		return false;
	}
}

/**
 * file_info implementation for the "Package" directory of a Test.
 */
class assessmentpath_test_package_file_info extends assessmentpath_test_basecontent_file_info {

	public function __construct(file_browser $browser, $context, $storedfile, $urlbase, $test) {
		parent::__construct($browser, $context, $storedfile, $urlbase, get_string('areapackage', 'scormlite'), $test);
	}
}

/**
 * file_info implementation for the "Content" directory of a Test.
 */
class assessmentpath_test_content_file_info extends assessmentpath_test_basecontent_file_info {

	public function __construct(file_browser $browser, $context, $storedfile, $urlbase, $test) {
		parent::__construct($browser, $context, $storedfile, $urlbase, get_string('areacontent', 'scormlite'), $test);
	}
}


//
// Events
//

function assessmentpath_trigger_path_event($eventname, $course, $cm, $activity, $other = []) {
	$data = [
		'objectid' => $activity->id,
		'context' => context_module::instance($cm->id),
	];
	if (!empty($other)) {
		$data['other'] = $other;
	}
	$eventclass = '\mod_assessmentpath\event\\' . $eventname;
	$event = $eventclass::create($data);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot('assessmentpath', $activity);
	$event->add_record_snapshot('course_modules', $cm);
	$event->trigger();
}

