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
 * @package   local_shop
 * @category  local
 * @author    Valery Fremaux (valery.fremaux@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot.'/local/shop/front/lib.php');
require_once($CFG->dirroot.'/local/shop/locallib.php');
require_once($CFG->dirroot.'/local/shop/classes/Shop.class.php');
require_once($CFG->dirroot.'/local/shop/classes/Catalog.class.php');

use local_shop\Catalog;
use local_shop\Shop;

$PAGE->set_url(new moodle_url('/blocks/shop_course_seats/ajax/service.php'));

$shopid = required_param('id', PARAM_INT);
$theshop = new Shop($shopid);
$thecatelog = new Catalog($theshop->catalogid);
$blockid = required_param('blockid', PARAM_INT);

$instance = $DB->get_record('block_instances', array('id' => $blockid));
$theblock = block_instance('shop_course_seats', $instance);
$context = context::instance_by_id($instance->parentcontextid);

if (!$course = $DB->get_record('course', array('id' => $context->instanceid))) {
    return 'course error';
}
require_login($course);

$products = block_shop_course_seats_get_products($USER->id);
$unassigned = 0;
$assigned = 0;

foreach ($products as $p) {
    if (!$p->instanceid) {
        $unassigned++;
    } else {
        $assigned++;
    }
}

$PAGE->set_pagelayout('embedded');
$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('block_shop_course_seats');
$renderer->load_context($theshop, $theblock);

$output = '';

$action = optional_param('action', '', PARAM_TEXT);
if ($action == 'addparticipant') {
    $pt = json_decode(required_param('participant', PARAM_TEXT));

    if (empty($pt->lastname) || empty($pt->lastname) || empty($pt->email)) {
        $result = get_string('missingdata', 'local_shop');
    } else {

        if (!isset($SESSION->shopseats)) {
            $SESSION->shopseats = new StdClass();
            $SESSION->shopseats->participants = array();
        }

        /*
         * We need making a loose matching here because there might be errors in the incoming forms.
         * We need anyway keep a matching heuristic sufficiant to match internal moodle users. Lastname
         * and email seems being sufficiant criteria to match a reliable user identity.
         */
        if ($moodleuser = $DB->get_record('user', array('lastname' => $pt->lastname, 'email' => $pt->email))) {
            $pt->moodleid = $moodleuser->id;
        }

        $pt->lastname = strtoupper($pt->lastname);
        $pt->firstname = ucwords($pt->firstname);
        $pt->city = strtoupper($pt->city);

        $SESSION->shopseats->participants[$pt->email] = $pt;
    }
    $action = 'participantlist';
}

if ($action == 'deleteparticipant') {
    $ptid = required_param('participantid', PARAM_TEXT);
    $requiredroles = $thecatelog->check_required_roles();

    if (isset($SESSION->shopseats->participants[$ptid])) {
        unset($SESSION->shopseats->participants[$ptid]);
    }

    if ($requiredroles) {
        foreach ($requiredroles as $role) {
            if (isset($SESSION->shopseats->users[$role][$ptid])) {
                unset($SESSION->shopseats->users[$role][$ptid]);
                @$SESSION->shopseats->assigns--;
            }
        }
    }

    $action = 'participantlist';
}

if ($action == 'participantlist') {
    if (!empty($result)) {
        $output .= $OUTPUT->box($result);
    }
    $output .= $renderer->participant_row(null);
    $i = 0;
    if (!empty($SESSION->shopseats->participants)) {
        foreach ($SESSION->shopseats->participants as $participant) {
            $output .= $renderer->participant_row($participant);
            $i++;
        }
    }
    for (; $i < $unassigned; $i++) {
        $output .= $renderer->participant_blankrow();
    }
}

if ($action == 'addassign') {
    $ptid = required_param('participantid', PARAM_TEXT);
    $role = required_param('role', PARAM_TEXT);
    $shortname = required_param('product', PARAM_TEXT);

    if (!isset($SESSION->shopseats->users)) {
        $SESSION->shopseats->users = array();
    }
    $SESSION->shopseats->users[$shortname][$role][$ptid] = $SESSION->shopseats->participants[$ptid];
    @$SESSION->shopseats->assigns[$shortname]++;
    $action = 'assignlistobj';
}

if ($action == 'deleteassign') {
    $ptid = required_param('participantid', PARAM_TEXT);
    $role = required_param('role', PARAM_TEXT);

    unset($SESSION->shopseats->users[$role][$ptid]);
    @$SESSION->shopseats->assigns--;
    $SESSION->shopseats->assigns = max(0, @$SESSION->shopseats->assigns); // Secures in case of failure...
    $action = 'assignlistobj';
}

if ($action == 'assignlist') {
    $role = required_param('role', PARAM_TEXT);
    $renderer->role_list($role);
}

if ($action == 'assignlistobj') {
    $requiredroles = $thecatelog->check_required_roles();

    $a = new StdClass;
    $a->role = required_param('role', PARAM_TEXT);
    foreach ($requiredroles as $role) {
        $a->content[$role] = $renderer->role_list($role);
    }

    $output = json_encode($a);
}

if ($action == 'assignalllistobj') {
    $requiredroles = $thecatelog->check_required_roles();

    $a = new StdClass;
    foreach ($requiredroles as $role) {
        $a->content[$role] = $renderer->role_list($role);
    }

    $output = json_encode($a);
}

echo $output;