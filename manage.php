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
 * Manage seats.
 *
 * @package   block_shop_course_seats
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (activeprolearn.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot.'/blocks/shop_course_seats/locallib.php');
require_once($CFG->dirroot.'/local/shop/classes/Shop.class.php');
require_once($CFG->dirroot.'/local/shop/classes/Product.class.php');
require_once($CFG->dirroot.'/local/shop/classes/BillItem.class.php');
require_once($CFG->dirroot.'/blocks/shop_course_seats/forms/shop_course_seats_user_form.php');
require_once($CFG->dirroot.'/group/lib.php');

use \local_shop\Shop;
use local_shop\Product;
use local_shop\BillItem;

$id = required_param('id', PARAM_INT); // Course id.
$blockid = required_param('blockid', PARAM_INT); // The current block id.

$instance = $DB->get_record('block_instances', ['id' => $blockid]);
$theblock = block_instance('shop_course_seats', $instance);
$theshop = new Shop($theblock->config->shopinstance);

$url = new moodle_url('/blocks/shop_course_seats/manage.php', ['id' => $id, 'blockid' => $blockid]);
$PAGE->set_url($url);

$PAGE->requires->js('/blocks/shop_course_seats/js/seats.js.php?blockid='.$blockid);

if (!$course = $DB->get_record('course',  ['id' => $id])) {
    throw new moodle_exception('coursemisconf');
}

require_course_login($course);

$context = context_course::instance($course->id);
$PAGE->set_context($context);

$PAGE->set_title(get_string('pluginname', 'block_shop_course_seats'));
$PAGE->set_heading(get_string('pluginname', 'block_shop_course_seats'));
$PAGE->navbar->add(get_string('pluginname', 'block_shop_course_seats'));

$products = block_shop_course_seats_get_products($USER->id);
$unassigned = 0;
$assigned = 0;
foreach ($products as $p) {
    if (!$p->instanceid) {
        $unassigned++;
        $unassignedinstances[] = $p;
    } else {
        $assigned++;
    }
}

$renderer = $PAGE->get_renderer('block_shop_course_seats');
$renderer->load_context($theshop, $theblock);

$groupopts = ['' => get_string('newgroup', 'block_shop_course_seats')];
if ($mygroups = groups_get_all_groups($COURSE->id, $USER->id)) {
    foreach ($mygroups as $gid => $g) {
        $groupopts[$gid] = $g->name;
    }
}

$userform = new ShopCourseSeatsUser_Form($url, ['groups' => $groupopts]);

if ($userform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

if ($data = $userform->get_data()) {
    $ret = '';

    // Create required group if missing.
    if (!empty($data->newgroup)) {
        if (!$group = $DB->get_record('groups', ['courseid' => $course->id, 'name' => $USER->id.'_'.$data->newgroup])) {
            $group = new StdClass;
            $group->courseid = $course->id;
            $group->name = $USER->id.'_'.$data->newgroup;
            $group->idnumber = '';
            $group->description = '';
            $group->descriptionformat = 0;
            $group->id = groups_create_group($group);

            // Add me to group.
            groups_add_member($group, $USER);
        }
    } else {
        $group = $DB->get_record('groups', ['id' => $data->group]);
    }

    // We are confirming assigning new users.
    foreach ($SESSION->shopseats->participants as $p) {

        // Get an unassigned instance to use it.
        if (!$prodrec = array_shift($unassignedinstances)) {
            $ret .= get_string('notenoughseats', 'block_shop_course_seats');
            break;
        }
        $product = new Product($prodrec->id);
        $billitem = new BillItem($product->currentbillitemid);

        if (empty($productinfo->supervisor)) {
            $supervisorrole = $DB->get_record('role', ['shortname' => 'teacher']);
        } else {
            $supervisorrole = $DB->get_record('role', ['shortname' => $productinfo->supervisor]);
        }

        // Check we must create one or already registered.
        if (!$potential = $DB->get_record('user', ['email' => $p->email])) {
            // Create a new account and bind it to the customer.

            $potential = shop_create_moodle_user($p, $billitem, $supervisorrole);
        }
        list($handler, $methodname) = $product->get_handler_info('assignseat_worker');

        // Add member to group.
        groups_add_member($group, $potential);

        $data = new Stdclass;
        $data->courseid = $course->id;
        $data->userid = $potential->id;
        $data->supervisor = $supervisorrole;

        $ret .= get_string('addinguser', 'block_shop_course_seats', $p)."\n";
        $ret .= $handler->{$methodname}($data, $product);
        $ret .= "\n";
    }
    $courseurl = new moodle_url('/course/view.php', ['id' => $id]);

    unset($SESSION->shopseats);

    // Generate some output.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('assignseats', 'block_shop_course_seats'));
    echo '<pre>';
    echo $ret;
    echo '</pre>';
    echo $OUTPUT->continue_button($courseurl);
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('assignseats', 'block_shop_course_seats'));


echo '<form name="participant">';

echo '<fieldset>';
echo '<legend>'.get_string('participants', 'local_shop').'</legend>';

echo '<div id="addparticipant" style="text-align:left"><p>';
print_string(($unassigned <= 1) ? 'participanthelper1' : 'participanthelper1plural' , 'local_shop', $unassigned);
print_string('participanthelper2', 'local_shop', $unassigned);
echo '</p></div>';

echo '<div id="addparticipant">';
echo $renderer->new_participant_row();
echo '</div>';
echo '</fieldset>';
echo '</form>';

echo '<table width="100%" id="participantlist" class="generaltable">';
$i = 0;
echo $renderer->participant_row(); // Print caption.
if (!empty($SESSION->shopseats->participants)) {
    foreach ($SESSION->shopseats->participants as $participant) {
        $participant->id = $i;
        echo $renderer->participant_row($participant, false);
        $i++;
    }
}

for (; $i < $unassigned; $i++) {
    echo $renderer->participant_blankrow();
}

echo '</table>';

$userform->set_data(['id' => $id, 'blockid' => $blockid]);
$userform->display();

echo $OUTPUT->footer();
