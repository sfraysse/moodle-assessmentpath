<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * xAPI transformation of an AssessmentPath event.
 *
 * @package    mod_assessmentpath
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_assessmentpath\xapi\statements;

defined('MOODLE_INTERNAL') || die();

/**
 * xAPI transformation of an AssessmentPath event.
 *
 * @package    mod_assessmentpath
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait attempt_statement {

    use utils;


    /**
     * Get the object.
     *
     * @return array
     */
    protected function statement_object() {
        return $this->xapi_sco();
    }

    /**
     * Build the context.
     *
     * @param string $activitytype Type of activity
     * @param bool $withsystem Include the system activity in the context?
     * @param string $vocabtype Type of activity
     * @param string $plugin Plugin where the implementation is located (ex. mod_forum)
     * @return array
     */
    protected function base_context($activitytype, $withsystem, $vocabtype, $plugin = null) {
        $context = parent::base_context($activitytype, $withsystem, $vocabtype, $plugin);

        // Set test to parent.
        $context['contextActivities']['parent'][0] = $this->xapi_test();

        // Add step to grouping.
        $context['contextActivities']['grouping'][] = $this->xapi_step();

        // Add activity to grouping.
        $context['contextActivities']['grouping'][] = $this->xapimodule;

        return $context;
    }

}
