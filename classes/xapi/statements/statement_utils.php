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
trait statement_utils {
    

    /**
     * Course module.
     *
     * @var stdClass $cm
     */
    protected $cm;

    /**
     * Test.
     *
     * @var stdClass $test
     */
    protected $test;

    /**
     * Step.
     *
     * @var stdClass $step
     */
    protected $step;

    /**
     * SCO.
     *
     * @var stdClass $sco
     */
    protected $sco;

    /**
     * xAPI module.
     *
     * @var array $xapimodule
     */
    protected $xapimodule;


    /**
     * Build the Statement.
     *
     * @return array
     */
    protected function statement() {
        $this->init_data();
        return parent::statement();
    }

    /**
     * Init data.
     *
     * @return void
     */
    protected function init_data() {
        global $DB;
        $this->cm = $DB->get_record('course_modules', ['id' => $this->event->contextinstanceid], '*', MUST_EXIST);
        $this->sco = $DB->get_record('scormlite_scoes', ['id' => $this->event->objectid], '*', MUST_EXIST);
        $this->test = $DB->get_record('assessmentpath_tests', ['sco' => $this->sco->id], '*', MUST_EXIST);
        $this->step = $DB->get_record('assessmentpath_steps', ['id' => $this->test->step], '*', MUST_EXIST);
        $this->xapimodule = $this->activities->get('assessmentpath', $this->cm->instance, false, 'module', 'assessmentpath', 'mod_assessmentpath');
    }

    /**
     * Get the step.
     *
     * @return array
     */
    protected function xapi_step() {

        // Define the id.
        $stepnum = $this->step->rank + 1;
        $id = $this->xapimodule['id'] . '/step/' . $stepnum;

        // Result.
        return [
            'objectType' => 'Activity',
            'id' => $id,
            'definition' => [
                'type' => 'http://vocab.xapi.fr/activities/training-sequence'
            ]
        ];
    }

    /**
     * Get the test.
     *
     * @return array
     */
    protected function xapi_test($fulldef = false) {

        // Define the id.
        $xapistep = $this->xapi_step();
        $testtype = $this->test->remediation ? 'remedial' : 'initial';
        $id = $xapistep['id'] . '/' . $testtype;

        // Base.
        $test = [
            'objectType' => 'Activity',
            'id' => $id,
            'definition' => [
                'type' => 'http://vocab.xapi.fr/activities/quiz'
            ]
        ];

        // Full definition.
        if ($fulldef) {
            $test['definition']['extensions'] = [
                'http://vocab.xapi.fr/extensions/remedial' => $this->test->remediation ? true : false
            ];
        }
        return $test;
    }

    /**
     * Get the sco.
     *
     * @return array
     */
    protected function xapi_sco() {

        // Define the id.
        $xapitest = $this->xapi_test();
        $id = $xapitest['id'] . '/sco';

        // Result.
        return [
            'objectType' => 'Activity',
            'id' => $id,
            'definition' => [
                'type' => $this->activities->types->type('sco'),
                'extensions' => [
                    'http://vocab.xapi.fr/extensions/standard' => $this->activities->types->standard('sco')
                ]
            ]
        ];
    }
    
}
