<?php

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
            $mform->addElement('select', 'group', get_string('groupname'), $this->_customdata['groups']);
        }

        $mform->addElement('text', 'newgroup', get_string('newgroup', 'block_shop_course_seats'));
        $mform->disabledIf('newgroup', 'group', 'neq', '');
        $mform->addRule('newgroup', 'required');

        $this->add_action_buttons(get_string('assignseats', 'block_shop_course_seats'));

    }
}