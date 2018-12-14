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


function xmldb_assessmentpath_upgrade($oldversion=0) {

	if ($oldversion < 2012010500) {
		// Assessment path table
		global $DB;
		$paths = $DB->get_records('assessmentpath');
		foreach ($paths as $path) {
			$colors = json_decode("[$path->colors]");
			if (is_object(reset($colors))) {
				$new_colors = array();
				foreach ($colors as $color) {
					$new_colors[] = $color->lt;
				}
				$path->colors = implode(',', $new_colors);
				$DB->update_record('assessmentpath', $path);
			}
		}
		// Settings
		$jsoncolors = '{"lt":50, "color":"#faa"}, {"lt":65, "color":"#fca"}, {"lt":75, "color":"#ffa"}, {"lt":101,"color":"#afa"}';
		$DB->set_field('config_plugins', 'value', $jsoncolors, array('plugin'=>'assessmentpath', 'name'=>'colors'));
	}

	return true;
}

?>
