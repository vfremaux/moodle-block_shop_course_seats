<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class ShopCourseSeatsUser_Form extends moodleform {

    function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);

        $this->add_action_buttons(get_string('assignseats', 'block_shop_course_seats'));

    }
}