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
 * @package     block_shop_course_seats
 * @category    blocks
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   2016 Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/formslib.php');

class ShopCourseSeatsUser_Form extends moodleform {

    function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);

        $mform->addElement('header', 'h0', get_string('group'), '');
        if (!empty($this->_customdata['groups'])) {
            $mform->addElement('select', 'group', get_string('group'), $this->_customdata['groups']);
        }

        $mform->addElement('text', 'newgroup', get_string('newgroup', 'block_shop_course_seats'));
        $mform->disabledIf('newgroup', 'group', 'neq', '');
        $mform->addRule('newgroup', null, 'required', null, 'client');
        $mform->setType('newgroup', PARAM_TEXT);

        $this->add_action_buttons(get_string('assignseats', 'block_shop_course_seats'));

    }
}