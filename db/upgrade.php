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
	global $CFG, $DB;

	$dbman = $DB->get_manager();

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

	/**
	 * Add a table to queue notifications.
	 */
	if ($oldversion < 2017110805) {

        // Define table assign_user_mapping to be created.
		$table = new xmldb_table('assessmentpath_notif_queue');
    
        // Adding fields to table assign_user_mapping.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
		$table->add_field('submitterid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
		$table->add_field('recipientid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
		$table->add_field('notification', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table assign_user_mapping.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		$dbman->create_table($table);

		upgrade_mod_savepoint(true, 2017110805, 'assessmentpath');
	}
    
	return true;
}

?>
