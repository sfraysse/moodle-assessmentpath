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

if (!defined('MOODLE_INTERNAL')) {
	die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');
require_once($CFG->dirroot.'/mod/assessmentpath/locallib.php');

$PAGE->requires->js('/mod/assessmentpath/javascript/jquery-1.4.4.min.js');
$PAGE->requires->js('/mod/assessmentpath/javascript/mod_edit.js');

class mod_assessmentpath_mod_form extends moodleform_mod {

	protected $steps = [];

	function definition()
	{
		global $CFG, $COURSE;
		$config = get_config('assessmentpath');
		$mform = $this->_form;


		//-------------------------------------------------------------------------------
		// Tabs
		
		$mform->addElement('html', '<div id="tabs">');
		$mform->addElement('html', '<span class="tab" id="maintab">'.get_string('maintab', 'assessmentpath').'</span>');
		$mform->addElement('html', '<span class="tab" id="stepstab">'.get_string('stepstab', 'assessmentpath').'</span>');
		$mform->addElement('html', '</div>');


		//-------------------------------------------------------------------------------
		// General
		
		$mform->addElement('header', 'general', get_string('general', 'form'));

		// Name
		$mform->addElement('text', 'name', get_string('title', 'scormlite'));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', null, 'required', null, 'client');

		// Code (kind of short name for the reports)
		$mform->addElement('text', 'code', get_string('code', 'scormlite'));
		$mform->setType('code', PARAM_TEXT);
		$mform->addRule('code', null, 'required', null, 'client');
		$mform->addHelpButton('code', 'code', 'scormlite');

		// Summary
        // KD2015-61 � add_intro_editor to be replaced by standard_intro_elements
		// $this->add_intro_editor();
        $this->standard_intro_elements();


		//-------------------------------------------------------------------------------
		// Colors
		
		scormlite_form_add_colors($mform, 'assessmentpath');


		//-------------------------------------------------------------------------------
		// Common settings
		
		$this->standard_coursemodule_elements();
		
		
		//-------------------------------------------------------------------------------
		// Steps
		
		$mform->addElement('header', 'steps', get_string('steps', 'assessmentpath'));

		// Prepare file picker
        // KD2014 - Removed for 2.5 compliance
		// $maxbytes = get_max_upload_file_size($CFG->maxbytes, $COURSE->maxbytes);
		// $mform->setMaxFileSize($maxbytes);
		
		// Get steps
		if ($this->_instance) $this->steps = assessmentpath_get_steps($this->_instance);
		else $this->steps = array();

		// Populate the form
		$this->add_steps();
		

		//-------------------------------------------------------------------------------
		// Buttons
		
		$this->add_action_buttons();

		//-------------------------------------------------------------------------------
		// Hidden
		
		// Tabs
		$mform->addElement('hidden', 'activetab', 'maintab');
		$mform->setType('activetab', PARAM_TEXT);

	}

	private function add_steps() {
		$mform = $this->_form;

		// Step number
		$stepnb = count($this->steps);
		if ($stepnb == 0) $stepnb = 1;
		$stepnb = optional_param('stepnb', $stepnb, PARAM_INT);
		$addstep = optional_param('addstep', '', PARAM_TEXT);
		if (!empty($addstep)) $stepnb += 1;
		$mform->addElement('hidden', 'stepnb', $stepnb);
		$mform->setType('stepnb', PARAM_INT);
		$mform->setConstants(array('stepnb'=>$stepnb));
		$mform->registerNoSubmitButton('addstep');

		// Loop
		for ($i = 0; $i < $stepnb; $i++) {
			$this->add_step($i, $stepnb);
		}
		
		// Add step button
		$mform->addElement('html', '<div class="pathcommands">');
		$mform->addElement('submit', 'addstep', get_string('insertstep', 'assessmentpath'));
		$mform->addElement('html', '</div>');
	}

	private function add_step($i, $stepnb) {
		global $OUTPUT;
		$mform = $this->_form;
		$config = get_config('assessmentpath');

		// Start
		$mform->addElement('html', '<div class="step" id="step'.$i.'">');

		// Commands
		$mform->addElement('html', '<div class="commands">');
		//     Advanced settings
		$mform->addElement('html', '<span class="advancedstep" id="step'.$i.'">');
		// $mform->addElement('html', get_string('advancedstep', 'assessmentpath'));
		
		// SF2017 - Icons
		//$mform->addElement('html', "<img src='".$OUTPUT->pix_url('t/edit')."' alt='".get_string('advancedstep', 'assessmentpath')."'/>");
		$mform->addElement('html', $OUTPUT->pix_icon('edit', get_string('advancedstep', 'assessmentpath'), 'mod_assessmentpath'));
		
		$mform->addElement('html', '</span>');
		//     Move down
		$mform->addElement('html', '<span class="movedownstep" id="step'.$i.'">');
		// $mform->addElement('html', get_string('movedownstep', 'assessmentpath'));

		// SF2017 - Icons
		//$mform->addElement('html', "<img src='".$OUTPUT->pix_url('t/down')."' alt='".get_string('movedownstep', 'assessmentpath')."'/>");
		$mform->addElement('html', $OUTPUT->pix_icon('down', get_string('movedownstep', 'assessmentpath'), 'mod_assessmentpath'));

		$mform->addElement('html', '</span>');
		//     Move up
		$mform->addElement('html', '<span class="moveupstep" id="step'.$i.'">');
		// $mform->addElement('html', get_string('moveupstep', 'assessmentpath'));

		// SF2017 - Icons
		//$mform->addElement('html', "<img src='".$OUTPUT->pix_url('t/up')."' alt='".get_string('moveupstep', 'assessmentpath')."'/>");
		$mform->addElement('html', $OUTPUT->pix_icon('up', get_string('moveupstep', 'assessmentpath'), 'mod_assessmentpath'));

		$mform->addElement('html', '</span>');
		//     Delete
		$mform->addElement('html', '<span class="deletestep" id="step'.$i.'">');
		// $mform->addElement('html', get_string('deletestep', 'assessmentpath'));

		// SF2017 - Icons
		//$mform->addElement('html', "<img src='".$OUTPUT->pix_url('t/delete')."' alt='".get_string('deletestep', 'assessmentpath')."'/>");
		$mform->addElement('html', $OUTPUT->pix_icon('delete', get_string('deletestep', 'assessmentpath'), 'mod_assessmentpath'));

		$mform->addElement('html', '</span>');
		//
		$mform->addElement('html', '</div>');

		$mform->addElement('html', '<div class="step-inner">');

		// ID
		$name = 'step_id['.$i.']';
		$mform->addElement('hidden', $name, 0);
		$mform->setType($name, PARAM_INT);

		// Deleted
		$name = 'step_deleted['.$i.']';
		$mform->addElement('hidden', $name, 0);
		$mform->setType($name, PARAM_INT);

		// Rank (steps ordering)
		$name = 'step_rank['.$i.']';
		$mform->addElement('hidden', $name, $i);
		$mform->setType($name, PARAM_INT);

		// Step params
		$mform->addElement('html', '<div class="stepsettings">');
		//      Code
		$name = 'step_code['.$i.']';
		$mform->addElement('text', $name, get_string('code', 'scormlite'));
		$mform->setType($name, PARAM_TEXT);
		$mform->addRule($name, null, 'required', null, 'client');
		//      Title
		$name = 'step_title['.$i.']';
		$mform->addElement('text', $name, get_string('title', 'scormlite'));
		$mform->setType($name, PARAM_TEXT);
		$mform->addRule($name, null, 'required', null, 'client');
		//
		$mform->addElement('html', '</div>');  // Step settings
		
		$mform->addElement('html', '<div class="steptests">');

		// Initial test
		$mform->addElement('html', '<div class="initialtest testsettings"><h5>'.get_string('initialtest','assessmentpath').'</h5>');
		$this->add_test($i, false);
		$mform->addElement('html', '</div>');
		
		// Remediation test
		$mform->addElement('html', '<div class="remediationtest testsettings"><h5>'.get_string('remediationtest','assessmentpath').'</h5>');
		$this->add_test($i, true);
		$mform->addElement('html', '</div>');

		$mform->addElement('html', '</div>');  // Step tests
		$mform->addElement('html', '</div>');  // Step Inner
		$mform->addElement('html', '</div>');  // Step 
	}

	private function add_test($i, $is_remediation) {
		$mform = $this->_form;
		$config = get_config('assessmentpath');

		// Test ID
		$prefix = assessmentpath_test::Form_Prefix($is_remediation, false);
		$name = $prefix.'id['.$i.']';
		$mform->addElement('hidden', $name, 0);
		$mform->setType($name, PARAM_INT);

		// SCO ID
		$scoprefix = assessmentpath_test::Form_Prefix($is_remediation, true);
		$name = $scoprefix.'id['.$i.']';
		$mform->addElement('hidden', $name, 0);
		$mform->setType($name, PARAM_INT);

		// Package file
		$name = $scoprefix.'packagefile_'.$i;
		$mform->addElement('filepicker', $name, get_string('package','scormlite'));

		// Advanced section  ----------------------------------------------------------------------
		$mform->addElement('html', '<div class="advanced">');

		// Manual opening
		$options = scormlite_get_manualopen_display_array($is_remediation);
		$name = $scoprefix.'manualopen['.$i.']';
		$mform->addElement('select', $name, get_string('manualopen', 'scormlite'), $options);
		$mform->setDefault($name, $config->manualopen);

		// Opening date
		$name = $scoprefix.'timeopen['.$i.']';
		$dep = $scoprefix.'manualopen['.$i.']';
		$mform->addElement('date_time_selector', $name, get_string('scormopen', 'scormlite'));
		$mform->disabledIf($name, $dep, 'neq', 0);

		// Closing date
		$name = $scoprefix.'timeclose['.$i.']';
		$dep = $scoprefix.'manualopen['.$i.']';
		$mform->addElement('date_time_selector', $name, get_string('scormclose', 'scormlite'));
		$mform->disabledIf($name, $dep, 'neq', 0);

		// Maximum time
		$name = $scoprefix.'maxtime['.$i.']';
		$mform->addElement('text', $name, get_string('maxtime','scormlite'), 'maxlength="5" size="5"');
		$mform->setDefault($name, $config->maxtime);
		$mform->setType($name, PARAM_INT);
		$mform->addHelpButton($name, 'maxtime', 'scormlite');
		$mform->addRule($name, null, 'numeric', null, 'client');
		$mform->addRule($name, null, 'nopunctuation', null, 'client');

		// Passing score
		$name = $scoprefix.'passingscore['.$i.']';
		$mform->addElement('text', $name, get_string('passingscore','scormlite'), 'maxlength="2" size="2"');
		$mform->setDefault($name, $config->passingscore);
		$mform->setType($name, PARAM_INT);
		$mform->addHelpButton($name, 'passingscore', 'scormlite');
		$mform->addRule($name, null, 'numeric', null, 'client');
		$mform->addRule($name, null, 'nopunctuation', null, 'client');

        // Max Attempts
		$name = $scoprefix . 'maxattempt[' . $i . ']';
		$mform->addElement('select', $name, get_string('maximumattempts', 'scormlite'), scormlite_get_attempts_array());
		$mform->addHelpButton($name, 'maximumattempts', 'scormlite');
		$mform->setDefault($name, $config->maxattempt);

        // What Attempt
		$name = $scoprefix . 'whatgrade[' . $i . ']';
		$mform->addElement('select', $name, get_string('whatgrade', 'scormlite'), scormlite_get_what_grade_array());
		$mform->disabledIf($name, $scoprefix . 'maxattempt[' . $i . ']', 'eq', 1);
		$mform->addHelpButton($name, 'whatgrade', 'scormlite');
		$mform->setDefault($name, $config->whatgrade);

		// Lock attempts after success
		$name = $scoprefix . 'lock_attempts_after_success[' . $i . ']';
		$mform->addElement('selectyesno', $name, get_string('lock_attempts_after_success', 'scormlite'));
		$mform->disabledIf($name, $scoprefix . 'maxattempt[' . $i . ']', 'eq', 1);
		$mform->setDefault($name, $config->lock_attempts_after_success);
		$mform->addHelpButton($name, 'lock_attempts_after_success', 'scormlite');

		// Framed / Popup Window
		$name = $scoprefix.'popup['.$i.']';
		$mform->addElement('select', $name, get_string('display', 'scormlite'), scormlite_get_popup_display_array());
		$mform->setDefault($name, $config->popup);

		// Chrono
		$name = $scoprefix.'displaychrono['.$i.']';
		$mform->addElement('selectyesno', $name, get_string('displaychrono', 'scormlite'));
		$mform->setDefault($name, $config->displaychrono);
		$mform->addHelpButton($name, 'displaychrono', 'scormlite');

		// Review access
		$name = $scoprefix . 'review_access[' . $i . ']';
		$mform->addElement('select', $name, get_string('review_access', 'scormlite'), scormlite_get_review_access_array());
		$mform->addHelpButton($name, 'review_access', 'scormlite');
		$mform->setDefault($name, $config->review_access);


		// Hidden fields  ----------------------------------------------------------------------

		$mform->addElement('hidden', $scoprefix.'containertype['.$i.']', 'assessmentpath');
        $mform->setType($scoprefix.'containertype['.$i.']', PARAM_ALPHA);  // KD2014 - For 2.5 compliance

		$mform->addElement('hidden', $scoprefix.'scormtype['.$i.']', 'local');
        $mform->setType($scoprefix.'scormtype['.$i.']', PARAM_ALPHA);  // KD2014 - For 2.5 compliance
        
		$mform->addElement('hidden', $scoprefix.'reference['.$i.']', '');
        $mform->setType($scoprefix.'reference['.$i.']', PARAM_ALPHA);  // KD2014 - For 2.5 compliance

		$mform->addElement('hidden', $scoprefix.'sha1hash['.$i.']', '');
        $mform->setType($scoprefix.'sha1hash['.$i.']', PARAM_RAW);  // KD2014 - For 2.5 compliance

		$mform->addElement('hidden', $scoprefix.'revision['.$i.']', 0);
        $mform->setType($scoprefix.'revision['.$i.']', PARAM_INT);  // KD2014 - For 2.5 compliance

		// The end
		$mform->addElement('html', '</div>');
	}


	/**
	 * Called when filling the default values (e.g when updating).
	 * @see moodleform_mod::data_preprocessing()
	 */
	function data_preprocessing(&$default_values) {
	
		// Colors
		scormlite_form_process_colors($default_values, 'assessmentpath');

		$i = 0;
		foreach ($this->steps as $step) {
		
			// Init step settings
			foreach ($step->data as $name => $value) {
				$default_values["step_{$name}[$i]"] = $value;
			}
			// Init tests
			foreach ($step->tests as $test) {
				// Test data
				$prefix = assessmentpath_test::Form_Prefix($test->data->remediation, false);
				foreach ($test->data as $name => $value) {
					$default_values["{$prefix}{$name}[$i]"] = $value;
				}
				// SCO data
				$scoprefix = assessmentpath_test::Form_Prefix($test->data->remediation, true);
				foreach ($test->scodata as $name => $value) {
					$default_values["{$scoprefix}{$name}[$i]"] = $value;
				}
				// Time
				if (empty($default_values['{$scoprefix}timeopen[$i]'])) {
					$default_values['{$scoprefix}timeopen[$i]'] = 0;
				}
				if (empty($default_values['{$scoprefix}timeclose[$i]'])) {
					$default_values['{$scoprefix}timeclose[$i]'] = 0;
				}
				// Packaging
				$elname = $scoprefix."packagefile_".$i;
				$draftitemid = file_get_submitted_draft_itemid($elname);
				file_prepare_draft_area($draftitemid, $this->context->id, 'mod_assessmentpath', 'package', $test->scodata->id);
				$default_values[$elname] = $draftitemid;
			}
			$i += 1;
		}
		// Parent processing
		parent::data_preprocessing($default_values);
	}

	function validation($data, $files)
	{
		global $CFG;
		$errors = array();
		foreach ($data["step_deleted"] as $index => $deleted) {
			if (!$deleted) {
				foreach (array(true, false) as $is_remediation) {
					$prefix = assessmentpath_test::Form_Prefix($is_remediation, false);
					$scoprefix = assessmentpath_test::Form_Prefix($is_remediation, true);

					// Check packages
					$data_key = "{$scoprefix}packagefile";
					$data_key_index = "{$scoprefix}packagefile_{$index}";
					$packagefile = $data[$data_key_index];
					if (empty($packagefile)) {
						// If no file
						if (!$is_remediation) {
							$errors[$data_key_index] = get_string('required');
						}
					} else {
						$files = $this->get_draft_files($data_key_index);
						if (!$files || count($files) < 1) {
							// If no file
							if (! $is_remediation) {
								$errors[$data_key_index] = get_string('required');
							}
							continue;
						}
						// Upload and try to unzip
						$file = reset($files);
						$filename = "{$CFG->tempdir}/assessmentpathimport/assessmentpath_".time();
						make_temp_directory('assessmentpathimport');
						$file->copy_content_to($filename);
						$packer = get_file_packer('application/zip');
						$filelist = $packer->list_files($filename);
						if (!is_array($filelist)) {
							// If not a package
							$errors[$data_key_index] = get_string('notvalidpackage', 'scormlite');
						} else {
							// Check if the index.html file is at the package root
							$indexfound = false;
							foreach ($filelist as $info) {
								if ($info->pathname == 'index.html') {
									$indexfound = true;
									break;
								}
							}
							if (!$indexfound) {
								$errors[$data_key_index] = get_string('notvalidpackage', 'scormlite');
							}
						}
						unlink($filename);
					}
					
					// Maximum time
					$data_key = "{$scoprefix}maxtime";
					$data_key_index = "{$data_key}[{$index}]";
					$maxtime = $data[$data_key][$index];
					if ($maxtime < 0) {
						$errors[$data_key_index] = get_string('notvalidmaxtime', 'scormlite');
					}
					
					// Passing score
					$data_key = "{$scoprefix}passingscore";
					$data_key_index = "{$data_key}[{$index}]";
					$passingscore = $data[$data_key][$index];
					if ($passingscore < 1 || $passingscore > 100) {
						$errors[$data_key_index] = get_string('notvalidpassingscore', 'scormlite');
					}
					
					// Colors
					scormlite_form_check_colors($data, $errors, 'assessmentpath');
					
				}
			}
		}

		// Return
		return array_merge($errors, parent::validation($data, $files));
	}


}
?>
