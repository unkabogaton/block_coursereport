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
        global $USER, $DB;

        // SQL Queries

        // number of course completed
        $coursecompleted = $DB->get_records_sql(
            'SELECT COUNT(id) AS count
        FROM mdl_course_completions
        WHERE userid = :useridx AND timecompleted != NULL',
            [
                'useridx' => $USER->id
            ]
        );

        $coursecompletedyear = $DB->get_records_sql(
            'SELECT COUNT(id) AS count
        FROM mdl_course_completions
        WHERE userid = :useridx
            AND timecompleted != NULL 
            AND FROM_UNIXTIME(timecompleted, "%Y") = :currentyear',
            [
                'useridx' => $USER->id,
                'currentyear' => date("Y")
            ]
        );

        // training hours (time spent on course pages)

        // Fetching the user's activity log
        $trainingtime = $DB->get_records_sql(
            'SELECT id, timecreated, target, action
                    FROM mdl_logstore_standard_log
                    WHERE userid = :useridx
                        ORDER BY timecreated',
            [
                'useridx' => $USER->id,
            ]
        );

        $trainingcount = $DB->get_records_sql(
            'SELECT COUNT(id) AS count
                    FROM mdl_logstore_standard_log
                    WHERE userid = :useridx',
            [
                'useridx' => $USER->id,
            ]
        );

        $differences1 = [];
        for ($i = 0; $i <= array_values($trainingcount)[0]->count - 2; $i++) {
            if (array_values($trainingtime)[$i]->target == "course" && array_values($trainingtime)[$i + 1]->action != "loggedin" && array_values($trainingtime)[$i + 1]->target != "user_login") {
                array_push($differences1, array_values($trainingtime)[$i + 1]->timecreated - array_values($trainingtime)[$i]->timecreated);
            }
        }
        if (array_sum($differences1) / 3600 < 10) {
            $training = round(array_sum($differences1) / 3600, 2);
        } elseif (array_sum($differences1) / 3600 < 100)
            $training =  round(array_sum($differences1) / 3600, 1);
        else {
            $training = round(array_sum($differences1) / 3600);
        }

        // Fetching the user's activity log in the current year
        $trainingtimeyear = $DB->get_records_sql(
            'SELECT id, timecreated, target, action
                    FROM mdl_logstore_standard_log
                    WHERE userid = :useridx
                        AND FROM_UNIXTIME(timecreated, "%Y") = :currentyear
                        ORDER BY timecreated',
            [
                'useridx' => $USER->id,
                'currentyear' => date("Y")
            ]
        );

        $trainingcountyear = $DB->get_records_sql(
            'SELECT COUNT(id) AS count
                    FROM mdl_logstore_standard_log
                    WHERE userid = :useridx
                        AND FROM_UNIXTIME(timecreated, "%Y") = :currentyear',
            [
                'useridx' => $USER->id,
                'currentyear' => date("Y")
            ]
        );

        $differences2 = [];
        for ($i = 0; $i <= array_values($trainingcountyear)[0]->count - 2; $i++) {
            if (array_values($trainingtimeyear)[$i]->target == "course" && array_values($trainingtimeyear)[$i + 1]->action != "loggedin" && array_values($trainingtimeyear)[$i + 1]->target != "user_login") {
                array_push($differences2, array_values($trainingtimeyear)[$i + 1]->timecreated - array_values($trainingtimeyear)[$i]->timecreated);
            }
        }
        if (array_sum($differences2) / 3600 < 10) {
            $trainingyear = round(array_sum($differences2) / 3600, 2);
        } elseif (array_sum($differences2) / 3600 < 100)
            $trainingyear =  round(array_sum($differences2) / 3600, 1);
        else {
            $trainingyear = round(array_sum($differences2) / 3600);
        }

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
            <h1 style="font-weight: 720;color:#FFFFFF;" class="m-4">' . array_values($coursecompleted)[0]->count . '</h1>
            </div>
            <div class="my-2 textcourse">Total Completed Courses</div>
            </div>' . "\n";

        // Total Completed Courses for the Year
        $this->content->text .=
            '<div class="col-md-3 col-6">
            <div class="boxcourse py-4 my-2 my-lg-0 ma-2">
            <h1 style="font-weight: 720;color:#FFFFFF;" class="m-4">' . array_values($coursecompletedyear)[0]->count . '</h1>
            </div>
            <div class="my-2 textcourse">Total Completed Courses for the Year</div>
            </div>' . "\n";

        // Total Training Hours
        $this->content->text .=
            '<div class="col-md-3 col-6">
            <div class="boxcourse py-4 my-2 my-lg-0 ma-2">
            <h1 style="font-weight: 720;color:#FFFFFF;" class="m-4">' . $training . '</h1>
            </div>
            <div class="my-2 textcourse">Total Training Hours</div>
            </div>' . "\n";

        // Total Training Hours for the Year
        $this->content->text .=
            '<div class="col-md-3 col-6">
            <div class="boxcourse py-4 my-2 my-lg-0 ma-2">
            <h1 style="font-weight: 720;color:#FFFFFF;" class="m-4">' . $trainingyear . '</h1>
            </div>
            <div class="my-2 textcourse">Total Training Hours for the Year</div>
            </div>' . "\n";
        $this->content->text .= '</div>' . "\n";
        return $this->content;
    }
}
