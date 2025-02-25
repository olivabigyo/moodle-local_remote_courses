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
 * External functions for returning course information.
 *
 * @package    local_mycoursesapi
 * @copyright  2024 ZHAW
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mycoursesapi\external;

defined('MOODLE_INTERNAL') || die;

require_once "$CFG->libdir/externallib.php";
require_once "$CFG->dirroot/enrol/externallib.php";

use core_enrol_external;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

class get_courses_by_eduid extends external_api
{
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_courses_by_eduid_parameters()
    {
        return new external_function_parameters(
            [
                'eduid' => new external_value(PARAM_EMAIL, 'eduid'),
            ]
        );
    }

    /**
     * Get a user's enrolled courses.
     *
     * This is a wrapper of core_enrol_get_users_courses(). It accepts
     * the eduid instead of the id.
     *
     * @param string $eduid
     * @return array
     */
    public static function get_courses_by_eduid($eduid)
    {
        global $DB;

        // Validate parameters passed from webservice.
        $params = self::validate_parameters(self::get_courses_by_eduid_parameters(), ['eduid' => $eduid]);

        // Extract the userid from the eduid.
        $eduIdFieldname = get_config('local_mycoursesapi', 'eduidfieldname');

        $userid = $DB->get_field_sql(
            "select u.id from {user} u
             join {user_info_data} uid ON u.id = uid.userid
             join {user_info_field} uif ON uif.id = uid.fieldid
             where u.email like '%students.zhaw.ch'
             and uid.data = :eduid
             and uif.shortname = :shortname",
            [
                'eduid' => $eduid,
                'shortname' => $eduIdFieldname
            ]
        );

        // Get the courses.
        $courses = core_enrol_external::get_users_courses($userid);
        $result = [];
        $favouritecourseids = [];
        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($userid));
        $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');
        foreach ($favourites as $favourite) {
            $favouritecourseids[] = $favourite->itemid;
        }

        foreach ($courses as $course) {
            $classification = course_classify_for_timeline((object) $course);
            $favourite = in_array($course['id'], $favouritecourseids) ? 1 : 0;

            $result[] = [
                'id' => $course['id'],
                'shortname' => $course['shortname'],
                'fullname' => $course['fullname'],
                'classification' => $classification,
                'description' => html_entity_decode(strip_tags($course['summary'])),
                'favourite' => $favourite,
                'visible' => $course['visible'],
            ];
        }

        return $result;
    }


    /**
     * Returns description of get_courses_by_eduid_returns() result value.
     *
     * @return \core_external\external_description
     */
    public static function get_courses_by_eduid_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id'        => new external_value(PARAM_INT, 'id of course'),
                    'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                    'fullname'  => new external_value(PARAM_RAW, 'long name of course'),
                    'classification' => new external_value(PARAM_RAW, 'timeline classification of course'),
                    'description' => new external_value(PARAM_RAW, 'course description'),
                    'favourite' => new external_value(PARAM_INT, '1 means favourite, 0 means not favourite'),
                    'visible'   => new external_value(PARAM_INT, '1 means visible, 0 means hidden course'),

                ]
            )
        );
    }
}
