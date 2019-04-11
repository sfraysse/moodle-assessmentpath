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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	require_once($CFG->dirroot.'/mod/scormlite/sharedlib.php');

	// Manual opening of the activity: Use dates / Open / Closed
	$settings->add(new admin_setting_configselect('assessmentpath/manualopen', get_string('manualopen','scormlite'), get_string('manualopendesc','scormlite'), 1, scormlite_get_manualopen_display_array()));

	// Maximum time
	$settings->add(new admin_setting_configtext('assessmentpath/maxtime', get_string('maxtime', 'scormlite'), get_string('maxtimedesc','scormlite'), 0, PARAM_INT));

	// Passing score
	$settings->add(new admin_setting_configtext('assessmentpath/passingscore', get_string('passingscore', 'scormlite'), get_string('passingscoredesc','scormlite'), 50, PARAM_INT));

	// Display mode: current window or popup
	$settings->add(new admin_setting_configselect('assessmentpath/popup', get_string('display','scormlite'), get_string('displaydesc','scormlite'), 0, scormlite_get_popup_display_array()));

	// Chrono
	$settings->add(new admin_setting_configcheckbox('assessmentpath/displaychrono', get_string('displaychrono', 'scormlite'), get_string('displaychronodesc','scormlite'), 1));

    // Maximum number of attempts
	$settings->add(new admin_setting_configselect('assessmentpath/maxattempt', get_string('maximumattempts', 'scormlite'), '', 1, scormlite_get_attempts_array()));

    // Score to keep when multiple attempts
	$settings->add(new admin_setting_configselect('assessmentpath/whatgrade', get_string('whatgrade', 'scormlite'), get_string('whatgradedesc', 'scormlite'), 0, scormlite_get_what_grade_array()));

    // Lock new attempts after success
	$settings->add(new admin_setting_configcheckbox('assessmentpath/lock_attempts_after_success', get_string('lock_attempts_after_success', 'scormlite'), get_string('lock_attempts_after_success_help', 'scormlite'), 1));

	// Colors
	$jsoncolors = '{"lt":50, "color":"#D53B3B"}, {"lt":65, "color":"#EF7A00"}, {"lt":75, "color":"#FDC200"}, {"lt":101,"color":"#85C440"}';
	$settings->add(new admin_setting_configtext('assessmentpath/colors', get_string('colors', 'scormlite'), get_string('colorsdesc','scormlite'), $jsoncolors, PARAM_RAW, 100));

	// Reports: display rank
	$settings->add(new admin_setting_configcheckbox('assessmentpath/displayrank', get_string('displayrank', 'scormlite'), get_string('displayrankdesc','scormlite'), 0));

    // Reports: review access
	$settings->add(new admin_setting_configselect('assessmentpath/review_access', get_string('review_access', 'scormlite'), get_string('review_access_help', 'scormlite'), 0, scormlite_get_review_access_array()));

    // Player close button
	$settings->add(new admin_setting_configcheckbox('assessmentpath/displayclosebutton', get_string('displayclosebutton', 'scormlite'), get_string('displayclosebuttondesc', 'scormlite'), 0));

}

