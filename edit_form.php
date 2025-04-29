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
 * Form to esit instance
 *
 * @package   block_shop_discounts
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (activeprolearn.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * form edition classs
 */
class block_shop_discounts_edit_form extends block_edit_form {

    /**
     * specific definition
     * @param moodle_form $mform
     */
    protected function specific_definition($mform) {

        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_shop_discounts'));
        $mform->setDefault('config_title', get_string('pluginname', 'block_shop_discounts'));
        $mform->setType('config_title', PARAM_MULTILANG);

        $mform->addElement('checkbox', 'config_hidetitle', get_string('confighidetitle', 'block_shop_discounts'));
        $mform->setDefault('config_hidetitle', 0);
        $mform->setType('config_hidetitle', PARAM_BOOL);
    }
}
