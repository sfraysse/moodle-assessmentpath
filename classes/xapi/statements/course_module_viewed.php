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

use logstore_trax\src\statements\core\course_module_viewed as core_course_module_viewed;

/**
 * xAPI transformation of an AssessmentPath event.
 *
 * @package    mod_assessmentpath
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends core_course_module_viewed {

    /**
     * Plugin.
     *
     * @var string $plugin
     */
    protected $plugin = 'mod_assessmentpath';

    /**
     * Activity type.
     *
     * @var string $activitytype
     */
    protected $activitytype = 'assessmentpath';

}
