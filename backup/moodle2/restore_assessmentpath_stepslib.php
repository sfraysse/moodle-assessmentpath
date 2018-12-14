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
 * Define all the restore steps that will be used by the restore_assessmentpath_activity_task
 */

/**
 * Structure step to restore one assessmentpath activity
 */
class restore_assessmentpath_activity_structure_step extends restore_activity_structure_step
{
	protected function define_structure()
	{
		$paths = array();
		$userinfo = $this->get_setting_value('userinfo');

		$paths[] = new restore_path_element('assessmentpath', '/activity/path');
		$paths[] = new restore_path_element('step',           '/activity/path/steps/step');
		$paths[] = new restore_path_element('test',           '/activity/path/steps/step/tests/test');
		$paths[] = new restore_path_element('sco',            '/activity/path/steps/step/tests/test/scoes/sco');
		if ($userinfo) {
			$paths[] = new restore_path_element('path_comment', '/activity/path/path_comments/path_comment');
			$paths[] = new restore_path_element('path_comment_user', '/activity/path/path_comments_users/path_comment_user');
			$paths[] = new restore_path_element('step_comment', '/activity/path/steps/step/step_comments/step_comment');
			$paths[] = new restore_path_element('track', '/activity/path/steps/step/tests/test/scoes/sco/tracks/track');
		}

		// Return the paths wrapped into standard activity structure
		return $this->prepare_activity_structure($paths);
	}

	protected function process_assessmentpath($data)
	{
		global $DB;

		$data = (object)$data;
		$data->course = $this->get_courseid();
		$data->timecreated = $this->apply_date_offset($data->timecreated);
		$data->timemodified = $this->apply_date_offset($data->timemodified);

		// insert the assessmentpath record
		$newitemid = $DB->insert_record('assessmentpath', $data);
		// immediately after inserting "activity" record, call this
		$this->apply_activity_instance($newitemid);
	}

	protected function process_step($data)
	{
		global $DB;

		$data = (object)$data;
		$oldid = $data->id;
		$data->activity = $this->get_new_parentid('assessmentpath');

		// insert the assessmentpath record
		$newitemid = $DB->insert_record('assessmentpath_steps', $data);

		$this->set_mapping('step', $oldid, $newitemid, true);
	}

	protected function process_test($data)
	{
		global $DB;

		$data = (object)$data;
		$oldid = $data->id;
		$data->step = $this->get_new_parentid('step');

		// insert the assessmentpath record
		$newitemid = $DB->insert_record('assessmentpath_tests', $data);

		$this->set_mapping('test', $oldid, $newitemid, true);
	}

	protected function process_sco($data)
	{
		global $DB;

		$data = (object)$data;
		$oldid = $data->id;
		$data->timeopen = $this->apply_date_offset($data->timeopen);
		$data->timeclose = $this->apply_date_offset($data->timeclose);

		// insert the assessmentpath record
		$newitemid = $DB->insert_record('scormlite_scoes', $data);

		$testid = $this->get_new_parentid('test');
		$DB->execute("UPDATE {assessmentpath_tests} SET sco=$newitemid WHERE id=$testid");

		$this->set_mapping('sco', $oldid, $newitemid, true);
	}

	protected function process_track($data)
	{
		global $DB;

		$data = (object)$data;

		$data->scoid = $this->get_new_parentid('sco');
		$data->userid = $this->get_mappingid('user', $data->userid);
		$data->timemodified = $this->apply_date_offset($data->timemodified);

		$DB->insert_record('scormlite_scoes_track', $data);
	}

	protected function process_path_comment($data)
	{
		global $DB;

		$data = (object)$data;
		$data->contextid = $this->get_new_parentid('assessmentpath');

		// insert the assessmentpath record
		$DB->insert_record('assessmentpath_comments', $data);
	}

	protected function process_path_comment_user($data)
	{
		global $DB;

		$data = (object)$data;
		$data->contextid = $this->get_new_parentid('assessmentpath');
		$data->userid = $this->get_mappingid('user', $data->userid);

		// insert the assessmentpath record
		$DB->insert_record('assessmentpath_comments', $data);
	}

	protected function process_step_comment($data)
	{
		global $DB;

		$data = (object)$data;
		$data->contextid = $this->get_new_parentid('step');

		// insert the assessmentpath record
		$DB->insert_record('assessmentpath_comments', $data);
	}

	protected function after_execute()
	{
		// Add assessmentpath related files, no need to match by itemname (just internally handled context)
		$this->add_related_files('mod_assessmentpath', 'intro', null);
		$this->add_related_files('mod_assessmentpath', 'content', 'sco');
		$this->add_related_files('mod_assessmentpath', 'package', 'sco');
	}
}
