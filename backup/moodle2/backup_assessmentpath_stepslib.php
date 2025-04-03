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

require_once(dirname(__FILE__).'/../../locallib.php');
require_once(dirname(__FILE__).'/../../report/reportlib.php');

/**
 * Define all the backup steps that will be used by the backup_assessmentpath_activity_task.
 */

/**
 * Define the complete assessmentpath structure for backup, with file and id annotations
 */
class backup_assessmentpath_activity_structure_step extends backup_activity_structure_step
{
	protected function define_structure()
	{
		// To know if we are including userinfo
		$userinfo = $this->get_setting_value('userinfo');

		// Define each element separated
		$path = new backup_nested_element('path', array('id'), array(
			'course', 'name', 'code', 'intro', 'introformat', 'colors', 'timecreated', 'timemodified'));

		$steps = new backup_nested_element('steps');
		$step = new backup_nested_element('step', array('id'), array(
			'activity', 'title', 'code', 'position'));

		$tests = new backup_nested_element('tests');
		$test = new backup_nested_element('test', array('id'), array(
			'step', 'sco', 'remediation'));

		$scoes = new backup_nested_element('scoes');
		$sco = new backup_nested_element('sco', array('id'), array(
			'containertype', 'scormtype', 'reference', 'sha1hash', 'md5hash', 'revision', 'timeopen', 'timeclose',
			'manualopen', 'maxtime', 'passingscore', 'maxattempt', 'whatgrade', 'lock_attempts_after_success', 'displaychrono', 
			'colors', 'popup', 'review_access'
		));

		if ($userinfo) {
			// P3
			$path_comments = new backup_nested_element('path_comments');
			$path_comment = new backup_nested_element('path_comment', array('id'), array(
				'contexttype', 'contextid', 'comment'));
			// P1
			$path_comments_users = new backup_nested_element('path_comments_users');
			$path_comment_user = new backup_nested_element('path_comment_user', array('id'), array(
				'contexttype', 'contextid', 'userid', 'comment'));
			// P4
			$step_comments = new backup_nested_element('step_comments');
			$step_comment = new backup_nested_element('step_comment', array('id'), array(
				'contexttype', 'contextid', 'comment'));

			$tracks = new backup_nested_element('tracks');
			$track = new backup_nested_element('track', array('scoid'), array(
				'userid', 'attempt', 'element', 'value', 'timemodified'));
		}

		// Build the tree
		$path->add_child($steps);
		$steps->add_child($step);
		$step->add_child($tests);
		$tests->add_child($test);
		$test->add_child($scoes);
		$scoes->add_child($sco);
		if ($userinfo) {
			$path->add_child($path_comments);
			$path_comments->add_child($path_comment);
			$path->add_child($path_comments_users);
			$path_comments_users->add_child($path_comment_user);
			$step->add_child($step_comments);
			$step_comments->add_child($step_comment);
			$sco->add_child($tracks);
			$tracks->add_child($track);
		}

		// Define sources
		$path->set_source_table('assessmentpath', array('id' => backup::VAR_ACTIVITYID));
		$step->set_source_table('assessmentpath_steps', array('activity' => backup::VAR_PARENTID));
		$test->set_source_table('assessmentpath_tests', array('step' => backup::VAR_PARENTID));
		$sql = '
			SELECT SS.*
			FROM {scormlite_scoes} SS
			INNER JOIN {assessmentpath_tests} T ON T.sco=SS.id
			WHERE T.id=?';
		$sco->set_source_sql($sql, array(backup::VAR_PARENTID));
		if ($userinfo) {
			$sql = '
				SELECT *
				FROM {assessmentpath_comments}
				WHERE contextid=? AND contexttype='.COMMENT_CONTEXT_GROUP_PATH.' AND userid IS NULL';
			$path_comment->set_source_sql($sql, array(backup::VAR_PARENTID));

			$sql = '
				SELECT *
				FROM {assessmentpath_comments}
				WHERE contextid=? AND contexttype='.COMMENT_CONTEXT_USER_PATH.' AND userid IS NOT NULL';
			$path_comment_user->set_source_sql($sql, array(backup::VAR_PARENTID));
			
			$sql = '
				SELECT *
				FROM {assessmentpath_comments}
				WHERE contextid=? AND contexttype='.COMMENT_CONTEXT_GROUP_STEP.' AND userid IS NULL';
			$step_comment->set_source_sql($sql, array(backup::VAR_PARENTID));

			$sql = '
				SELECT SST.*
				FROM {scormlite_scoes_track} SST
				INNER JOIN {scormlite_scoes} SS ON SS.id=SST.scoid
				INNER JOIN {assessmentpath_tests} T ON T.sco=SS.id
				WHERE T.id=?';
			$track->set_source_sql($sql, array(backup::VAR_PARENTID));
//			$track->set_source_table('scormlite_scoes_track', array('scoid' => backup::VAR_PARENTID));
		}

		// Define id annotations
		if ($userinfo) {
			$path_comment_user->annotate_ids('user', 'userid');
			$track->annotate_ids('user', 'userid');
		}

		// Define file annotations
		$path->annotate_files('mod_assessmentpath', 'intro', null); // This file area hasn't itemid
		$sco->annotate_files('mod_assessmentpath', 'content', 'id');
		$sco->annotate_files('mod_assessmentpath', 'package', 'id');

		// Return the root element (scorm), wrapped into standard activity structure
		return $this->prepare_activity_structure($path);
	}
}
