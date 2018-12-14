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

require_once($CFG->dirroot . '/mod/assessmentpath/backup/moodle2/restore_assessmentpath_stepslib.php'); // Because it exists (must)

/**
 * assessmentpath restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_assessmentpath_activity_task extends restore_activity_task {

	/**
	 * Define (add) particular settings this activity can have
	 */
	protected function define_my_settings() {
		// No particular settings for this activity
	}

	/**
	 * Define (add) particular steps this activity can have
	 */
	protected function define_my_steps() {
		// Choice only has one structure step
		$this->add_step(new restore_assessmentpath_activity_structure_step('assessmentpath_structure', 'assessmentpath.xml'));
	}

	/**
	 * Define the contents in the activity that must be
	 * processed by the link decoder
	 */
	static public function define_decode_contents() {
		$contents = array();

		$contents[] = new restore_decode_content('assessmentpath', array('intro'), 'assessmentpath');

		return $contents;
	}

	/**
	 * Define the decoding rules for links belonging
	 * to the activity to be executed by the link decoder
	 */
	static public function define_decode_rules() {
		$rules = array();

		$rules[] = new restore_decode_rule('ASSESSMENTPATHVIEWBYID', '/mod/assessmentpath/view.php?id=$1', 'course_module');
		$rules[] = new restore_decode_rule('ASSESSMENTPATHINDEX', '/mod/assessmentpath/index.php?id=$1', 'course');

		return $rules;

	}

	/**
	 * Define the restore log rules that will be applied
	 * by the {@link restore_logs_processor} when restoring
	 * assessmentpath logs. It must return one array
	 * of {@link restore_log_rule} objects
	 */
	static public function define_restore_log_rules() {
		$rules = array();

		$rules[] = new restore_log_rule('assessmentpath', 'add', 'view.php?id={course_module}', '{assessmentpath}');
		$rules[] = new restore_log_rule('assessmentpath', 'update', 'view.php?id={course_module}', '{assessmentpath}');
		$rules[] = new restore_log_rule('assessmentpath', 'view', 'view.php?id={course_module}', '{assessmentpath}');
		$rules[] = new restore_log_rule('assessmentpath', 'choose', 'view.php?id={course_module}', '{assessmentpath}');
		$rules[] = new restore_log_rule('assessmentpath', 'choose again', 'view.php?id={course_module}', '{assessmentpath}');
		$rules[] = new restore_log_rule('assessmentpath', 'report', 'report.php?id={course_module}', '{assessmentpath}');

		return $rules;
	}

	/**
	 * Define the restore log rules that will be applied
	 * by the {@link restore_logs_processor} when restoring
	 * course logs. It must return one array
	 * of {@link restore_log_rule} objects
	 *
	 * Note this rules are applied when restoring course logs
	 * by the restore final task, but are defined here at
	 * activity level. All them are rules not linked to any module instance (cmid = 0)
	 */
	static public function define_restore_log_rules_for_course() {
		$rules = array();

		// Fix old wrong uses (missing extension)
		$rules[] = new restore_log_rule('assessmentpath', 'view all', 'index?id={course}', null, null, null, 'index.php?id={course}');
		$rules[] = new restore_log_rule('assessmentpath', 'view all', 'index.php?id={course}', null);

		return $rules;
	}
}
