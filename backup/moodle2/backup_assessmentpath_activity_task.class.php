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

require_once($CFG->dirroot . '/mod/assessmentpath/backup/moodle2/backup_assessmentpath_stepslib.php'); // Because it exists (must)
//require_once($CFG->dirroot . '/mod/assessmentpath/backup/moodle2/backup_assessmentpath_settingslib.php'); // Because it exists (optional)

/**
 * assessmentpath backup task that provides all the settings and steps to perform one
 * complete backup of the activity.
 * @see http://docs.moodle.org/dev/Backup_2.0_for_developers
 */
class backup_assessmentpath_activity_task extends backup_activity_task {

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
		$this->add_step(new backup_assessmentpath_activity_structure_step('assessmentpath_structure', 'assessmentpath.xml'));
	}

	/**
	 * Code the transformations to perform in the activity in
	 * order to get transportable (encoded) links
	 */
	static public function encode_content_links($content) {
		global $CFG;

		$base = preg_quote($CFG->wwwroot,"/");

		// Link to the list of assessmentpaths
		$search="/(".$base."\/mod\/assessmentpath\/index.php\?id\=)([0-9]+)/";
		$content= preg_replace($search, '$@assessmentpathINDEX*$2@$', $content);

		// Link to assessmentpath view by moduleid
		$search="/(".$base."\/mod\/assessmentpath\/view.php\?id\=)([0-9]+)/";
		$content= preg_replace($search, '$@assessmentpathVIEWBYID*$2@$', $content);

		return $content;
	}
}
