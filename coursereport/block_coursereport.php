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
 * Form for editing HTML block instances.
 *
 * @package   block_coursereport
 * @copyright   2022 Anthon anthonralph2@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class block_coursereport extends block_base
{
    function init()
    {
        $this->title = "User Analytics";
    }
    // function for getting the differences between the the timestamps of going to course page and going to other oage from course page
    function get_content()
    {
        // importing the global variables
        global $USER, $DB;

        // getting the id of the courses with completed modules
        $courses = $DB->get_records_sql(
            'SELECT DISTINCT mdl_course_modules.course as id
                FROM mdl_course_modules_completion
                INNER JOIN mdl_course_modules ON mdl_course_modules_completion.coursemoduleid = mdl_course_modules.id 
                WHERE userid = :useridx AND completionstate = 1',
            [
                'useridx' => $USER->id,
            ]
        );

        // getting the id of the courses with completed modules in the current year
        $coursesyear = $DB->get_records_sql(
            'SELECT DISTINCT mdl_course_modules.course as id
                FROM mdl_course_modules_completion
                INNER JOIN mdl_course_modules ON mdl_course_modules_completion.coursemoduleid = mdl_course_modules.id 
                WHERE userid = :useridx AND completionstate = 1 AND FROM_UNIXTIME(timemodified, "%Y") = :currentyear',
            [
                'useridx' => $USER->id,
                'currentyear' => date("Y")
            ]
        );

        // getting the array of id of completed courses
        $completedcourses = $this->completedcourses($courses);

        // getting the array of id of completed courses for the year
        $completedcoursesyear = $this->completedcourses($coursesyear);

        // Rendered content
        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '<style> div.boxcourse {background-color: #29296E; margin: auto; text-align: center; color: white;}
                                        div.textcourse {color: #29296E; text-align: center;}  
                                </style>' . "\n";
        $this->content->text .= '<div class="row">' . "\n";

        // Total Completed Courses
        $this->content->text .=
            '<div class="col-md-3 col-6">
            <div class="boxcourse py-4 my-2 my-lg-0 ma-2">
            <h1 style="font-weight: 720;color:#FFFFFF;" class="m-4">' . count($completedcourses) . '</h1>
            </div>
            <div class="my-2 textcourse">Total Completed Courses</div>
            </div>' . "\n";

        // Total Completed Courses for the Year
        $this->content->text .=
            '<div class="col-md-3 col-6">
            <div class="boxcourse py-4 my-2 my-lg-0 ma-2">
            <h1 style="font-weight: 720;color:#FFFFFF;" class="m-4">' . count($completedcoursesyear) . '</h1>
            </div>
            <div class="my-2 textcourse">Total Completed Courses for the Year</div>
            </div>' . "\n";

        // Total Training Hours
        $this->content->text .=
            '<div class="col-md-3 col-6">
            <div class="boxcourse py-4 my-2 my-lg-0 ma-2">
            <h1 style="font-weight: 720;color:#FFFFFF;" class="m-4">' . $this->totaltraining($completedcourses) . '</h1>
            </div>
            <div class="my-2 textcourse">Total Training Hours</div>
            </div>' . "\n";

        // Total Training Hours for the Year
        $this->content->text .=
            '<div class="col-md-3 col-6">
            <div class="boxcourse py-4 my-2 my-lg-0 ma-2">
            <h1 style="font-weight: 720;color:#FFFFFF;" class="m-4">' . $this->totaltraining($completedcoursesyear) . '</h1>
            </div>
            <div class="my-2 textcourse">Total Training Hours for the Year</div>
            </div>' . "\n";
        $this->content->text .= '</div>' . "\n";
        return $this->content;
    }

    // function for getting the completed courses using object of id of courses with completed modules
    function completedcourses($courses)
    {
        global $DB;
        $completedcoursesid = [];
        foreach ($courses as $course) {
            $modules = $DB->get_records_sql(
                'SELECT COUNT(id) AS count
                    FROM mdl_course_modules
                    WHERE course = :courseid',
                [
                    'courseid' => $course->id,
                ]
            );
            $completedmodules = $DB->get_records_sql(
                'SELECT COUNT(mdl_course_modules_completion.id) AS count
                    FROM mdl_course_modules_completion
                    INNER JOIN mdl_course_modules ON mdl_course_modules_completion.coursemoduleid = mdl_course_modules.id 
                    WHERE mdl_course_modules.course = :courseid AND completionstate = 1',
                [
                    'courseid' => $course->id,
                ]
            );
            $completion = array_values($completedmodules)[0]->count / array_values($modules)[0]->count;
            if ($completion == 1) {
                array_push($completedcoursesid, $course->id);
            }
        }
        return $completedcoursesid;
    }

    // function for getting the sum of training hours of completed courses using array of id of completed courses
    function totaltraining($completedcourse)
    {
        global $DB;
        $hours = 0;
        foreach ($completedcourse as $completed) {
            $trainsample = $DB->get_records_sql(
                'SELECT charvalue
                FROM mdl_customfield_data
                WHERE fieldid = 8
                AND instanceid = :completed',
                ['completed' => $completed]
            );
            if (array_values($trainsample)) {
                $hours += array_values($trainsample)[0]->charvalue;
            }
        }
        return $hours;
    }
}
