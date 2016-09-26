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
 * Main block implementation.
 *
 * @package    block_shop_course_seats
 * @category   blocks
 * @copyright  2013 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This bloc provides a local way to manage the customer's owned seats from within
 * the course, without any need ofother screens or services.
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/shop_course_seats/locallib.php');
require_once($CFG->dirroot.'/local/shop/classes/Shop.class.php');

use \local_shop\Shop;

class block_shop_course_seats extends block_base {

    public function init() {
        $this->title = get_string('blockname', 'block_shop_course_seats');
    }

    public function applicable_formats() {
        return array('all' => false, 'my' => false, 'course' => true);
    }

    public function specialization() {
        return false;
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function get_content() {
        global $USER, $DB, $COURSE, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';

        $coursecontext = context_course::instance($COURSE->id);

        // Hide the blocks to everyone that does not manage products.
        if (!has_capability('block/shop_course_seats:manage', $coursecontext)) {
            $this->content->text = '';
            return $this->content;
        }

        $renderer = $PAGE->get_renderer('block_shop_course_seats');

        if (!isset($this->config)) {
            $this->config = new StdClass;
        }
        if (empty($this->config->shopinstance)) {
            // Defaults to first shop.
            $this->config->shopinstance = 1;
        }

        $theshop = new Shop($this->config->shopinstance, true);
        $renderer->load_context($theshop, $this);

        // Fetch products that are seats assigned to this course, or notassigned.
        // Non assigned products will be filtered for course later.
        $products = block_shop_course_seats_get_products($USER->id);
        if ($products) {
            $wide = false;

            // Check we are not in central position of a page format.
            if ($COURSE->format == 'page') {
                $blockposition = $DB->get_record('block_positions', array('blockinstanceid' => $this->instance->id));
                if (!$blockposition) {
                    if (@$this->defaultregion == 'main') {
                        $wide = true;
                    }
                } else {
                    if ($blockposition->region == 'main') {
                        $wide = true;
                    }
                }
            }

            // Filter out assignable but not for this course.
            $hasassignable = false;

            foreach ($products as $pid => $p) {
                $params = $this->_decode_url_parms($p->productiondata);
                $expectedcourses = false;
                if (!empty($params['allowedcourses'])) {
                    $expectedcourses = explode(',', $params['allowedcourses']);
                }

                if (!empty($expectedcourses) && !in_array($COURSE->id, $expectedcourses)) {
                    unset($products[$pid]);
                } else {
                    if (!$p->instanceid) {
                        $hasassignable = true;
                    }
                }
            }

            if (empty($products)) {
                $this->content->text = get_string('noseatsforthiscourse', 'block_shop_course_seats');
                return $this->content;
            }

            if ($wide) {
                $this->content->text = $renderer->product_table_wide($products);
            } else {
                $this->content->text = $renderer->product_table_narrow($products);
            }

            if ($hasassignable) {
                $params = array('id' => $COURSE->id, 'blockid' => $this->instance->id);
                $manageurl = new moodle_url('/blocks/shop_course_seats/manage.php', $params);
                $managestr = get_string('manageseats', 'block_shop_course_seats');
                $this->content->footer = '<a href="'.$manageurl.'">'.$managestr.'</a>';
            }

        } else {
            $this->content->text = get_string('noseats', 'block_shop_course_seats');
        }

        return $this->content;
    }

    /**
     * Hide the title bar when none set.
     */
    public function hide_header() {
        return false;
    }

    public function get_context_product_info($product) {
        global $DB, $OUTPUT;

        if (empty($product->instanceid)) {
            return '';
        }

        $str = '';
        switch ($product->contexttype) {
            case 'user_enrolment':
                $ue = $DB->get_record('user_enrolments', array('id' => $product->instanceid));
                $user = $DB->get_record('user', array('id' => $ue->userid));
                $courseid = $DB->get_field('enrol', 'courseid', array('id' => $ue->enrolid));
                $str .= $OUTPUT->box_start();
                $str .= get_string('assignedto', 'block_shop_course_seats', fullname($user));
                $str .= $OUTPUT->box_end();
        }
        return $str;
    }

    private function _decode_url_parms($urlparms) {
        $params = array();
        $parms = explode('&', $urlparms);
        foreach ($parms as $p) {
            list($key, $value) = explode('=', $p);
            $params[$key] = $value;
        }
        return $params;
    }

    public function get_required_javascript() {
        global $PAGE;

        $PAGE->requires->js('/blocks/shop_course_seats/js/js.js');
    }
}
