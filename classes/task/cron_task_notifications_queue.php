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

namespace mod_assessmentpath\task;

class cron_task_notifications_queue extends \core\task\scheduled_task  {

    /**
     * Return the task's name as shown in admin screens.
     */
    public function get_name()
    {
        return get_string('crontask_notifications_queue', 'mod_assessmentpath');
    }
    
    /**
     * Run forum cron.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assessmentpath/cronlib.php');
        assessmentpath_cron();
    }

}
