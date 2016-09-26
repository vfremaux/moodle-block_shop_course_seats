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
 * Main renderer for this block.
 *
 * @package    block_shop_course_seats
 * @category   blocks
 * @copyright  2013 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/shop/classes/Catalog.class.php');
require_once($CFG->dirroot.'/local/shop/classes/Tax.class.php');

use \local_shop\Catalog;
use \local_shop\Tax;

class block_shop_course_seats_renderer extends plugin_renderer_base {

    // Context references.
    protected $theblock; // A course seat block instance.

    protected $theshop;

    protected $thecatalog;

    public $context;

    public $view;

    /**
     * Loads the renderer with contextual objects. Most of the renderer function need
     * at least a shop instance.
     */
    public function load_context(&$theshop, &$theblock = null) {

        $this->theshop = $theshop;
        $this->thecatalog = new Catalog($this->theshop->catalogid);
        $this->theblock = $theblock;

        if (!empty($this->theblock->instance->id)) {
            $this->context = context_block::instance($this->theblock->instance->id);
            $this->theblock->id = $this->theblock->instance->id;
        } else {
            $this->context = context_system::instance();
            $this->theblock = new Stdclass();
            $this->theblock->id = 0;
        }
    }

    public function check_context() {
        if (empty($this->theshop) || empty($this->thecatalog)) {
            throw new coding_exception('the renderer is not ready for use. Load a shop and a catalog before calling.');
        }
    }

    /**
     * @param object $theblock the current course seat block instance
     * @param array $seats
     */
    public function product_table_wide($seats) {
        global $COURSE, $OUTPUT;

        $pidstr = get_string('pid', 'block_shop_course_seats');
        $startdatestr = get_string('startdate', 'block_shop_course_seats');
        $enddatestr = get_string('enddate', 'block_shop_course_seats');
        $productlinkstr = get_string('product', 'block_shop_course_seats');
        $statusstr = get_string('status', 'block_shop_course_seats');

        $availablecount = 0;
        $runningcount = 0;
        $expiredcount = 0;

        $producttable = new html_table();
        $producttable->head = array("<b>$pidstr</b>", "<b>$startdatestr</b>", "<b>$enddatestr</b>",
            "<b>$productlinkstr</b>", "<b>$statusstr</b>");
        $producttable->width = '100%';
        $producttable->size = array('10%', '10%', '10%', '40%', '30%');
        $producttable->align = array('left', 'left', 'left', 'left', 'right');

        foreach ($seats as $p) {
            $pstart = ($p->startdate) ? date('Y/m/d h:i', $p->startdate) : 'N.C.';
            $pstr = '['.$p->code.'] '.$p->name;
            $params = array('id' => $COURSE->id,
                            'shopid' => $this->theblock->config->shopinstance,
                            'blockid' => $this->theblock->instance->id,
                            'pid' => $p->id);
            $purl = new moodle_url('/blocks/shop_course_seats/product/view.php', $params);
            $status = '';
            $productext = $this->theblock->get_context_product_info($p);
            $productline = '<span class="cs-course-seat-code">['.$p->reference.']</span>'.$productext;
            if ($p->renewable) {
                $pend = ($p->enddate) ? date('Y/m/d H:i', $p->enddate) : 'N.C.';
                if (time() > $p->enddate) {
                    // Expired.
                    $status = '<span class="cs-course-seat-expired">'.get_string('expired', 'block_shop_course_seats').'</span>';
                    $pend = '<span class="cs-course-seat-expireddate">'.$pend.'</span>';
                    $expiredcount++;
                } else if (time() > $p->enddate - DAYSECS * 3) {
                    // Expiring.
                    $status = '<span class="cs-course-seat-expiring">'.get_string('expiring', 'block_shop_course_seats').'</span>';
                    $pend = '<span class="cs-course-seat-expiringdate">'.$pend.'</span>';
                } else {
                    // Running.
                    $status = '<span class="cs-course-seat-running">'.get_string('running', 'block_shop_course_seats').'</span>';
                    $pend = '<span class="cs-course-seat-runningdate">'.$pend.'</span>';
                    $runningcount++;
                }
                $producttable->data[] = array($productline, $pstart, $pend, '<a href="'.$purl.'">'.$pstr.'</a>', $status);
            } else {
                if ($p->instanceid) {
                    $status = '<span class="cs-course-seat-running">'.get_string('running', 'block_shop_course_seats').'</span>';
                    $runningcount++;
                } else {
                    $status = '<span class="cs-course-seat-unused">'.get_string('available', 'block_shop_course_seats').'</span>';
                    $availablecount++;
                }
                $producttable->data[] = array($productline, $pstart, 'N.C.', '<a href="'.$purl.'">'.$pstr.'</a>', $status);
            }
        }

        $globalcounts = '<div id="cs-course-seat-shorts">';
        $globalcounts .= '<div id="cs-course-seat-toggler">';
        $globalcounts .= '<a href="javascript:toggle_course_seats()">';
        $globalcounts .= '<img id="cs-course-seat-toggleimg" src="'.$OUTPUT->pix_url('t/switch_plus').'">';
        $globalcounts .= '</a></div>';

        $globalcounts .= '<div id="cs-course-seat-globalcounts">';
        $globalcounts .= get_string('available', 'block_shop_course_seats').': <b>'.$availablecount.'</b>&nbsp;&nbsp;&nbsp;';
        $globalcounts .= get_string('running', 'block_shop_course_seats').': <b>'.$runningcount.'</b>&nbsp;&nbsp;&nbsp;';
        if ($expiredcount) {
            $globalcounts .= get_string('expired', 'block_shop_course_seats').': <b>'.$expiredcount.'</b>';
        }
        $globalcounts .= '</div>';
        $globalcounts .= '</div>';

        $str = $globalcounts;

        $str .= '<div id="cs-course-seats" >';
        $str .= html_writer::table($producttable);

        return $str;
    }

    public function product_table_narrow($seats) {
        global $COURSE, $OUTPUT;

        $productlinkstr = get_string('product', 'block_shop_course_seats');
        $statusstr = get_string('status', 'block_shop_course_seats');

        $availablecount = 0;
        $runningcount = 0;
        $expiredcount = 0;

        $producttable = new html_table();
        $producttable->head = array("<b>$productlinkstr</b>", "<b>$statusstr</b>");
        $producttable->width = '100%';
        $producttable->size = array('70%', '30%');
        $producttable->align = array('left', 'right');

        foreach ($seats as $p) {
            $pstart = ($p->startdate) ? date('Y/m/d h:i', $p->startdate) : 'N.C.';
            $pstr = '['.$p->code.'] '.$p->name;
            $params = array('id' => $COURSE->id,
                            'shopid' => $this->theblock->config->shopinstance,
                            'blockid' => $this->theblock->instance->id,
                            'pid' => $p->id);
            $purl = new moodle_url('/blocks/shop_course_seats/product/view.php', $params);
            $status = '';
            if ($p->renewable) {
                $pend = ($p->enddate) ? date('Y/m/d h:i', $p->enddate) : 'N.C.';
                if (time() > $p->enddate) {
                    // Expired.
                    $status = '<span class="cs-course-seat-expired">'.get_string('expired', 'block_shop_course_seats').'</span>';
                    $pend = '<span class="cs-course-seat-expireddate">'.$pend.'</span>';
                    $expiredcount++;
                } else if (time() > $p->enddate - DAYSECS * 3) {
                    // Expiring.
                    $status = '<span class="cs-course-seat-expiring">'.get_string('expiring', 'block_shop_course_seats').'</span>';
                    $pend = '<span class="cs-course-seat-expiringdate">'.$pend.'</span>';
                } else {
                    // Running.
                    $status = '<span class="cs-course-seat-running">'.get_string('running', 'block_shop_course_seats').'</span>';
                    $pend = '<span class="cs-course-seat-runningdate">'.$pend.'</span>';
                    $runningcount++;
                }
                $productline = '<a href="'.$purl.'" title="'.$p->reference.'">'.$pstr.'</a><br/>';
                $productline .= '<span class="smalltext">'.$pstart.' - '.$pend.'</span>';
                $producttable->data[] = array($productline, $status);
            } else {
                if ($p->instanceid) {
                    $status = '<span class="cs-course-seat-running">'.get_string('running', 'block_shop_course_seats').'</span>';
                    $runningcount++;
                } else {
                    $status = '<span class="cs-course-seat-unused">'.get_string('available', 'block_shop_course_seats').'</span>';
                    $availablecount++;
                }
                $producttable->data[] = array('<a href="'.$purl.'" title="'.$p->reference.'">'.$pstr.'</a><br/>'.$pstart, $status);
            }
        }

        $globalcounts = '<div id="cs-course-seat-shorts">';
        $globalcounts .= '<div id="cs-course-seat-toggler">';
        $globalcounts .= '<a href="javascript:toggle_course_seats()">';
        $globalcounts .= '<img id="cs-course-seat-toggleimg" src="'.$OUTPUT->pix_url('t/switch_plus').'"></a></div>';
        $globalcounts .= '<div id="cs-course-seat-globalcounts">';
        $globalcounts .= get_string('available', 'block_shop_course_seats').': <b>'.$availablecount.'</b>&nbsp;&nbsp;&nbsp;';
        $globalcounts .= get_string('running', 'block_shop_course_seats').': <b>'.$runningcount.'</b>&nbsp;&nbsp;&nbsp;';
        if ($expiredcount) {
            $globalcounts .= get_string('expired', 'block_shop_course_seats').': <b>'.$expiredcount.'</b>';
        }
        $globalcounts .= '</div>';
        $globalcounts .= '</div>';

        $str = $globalcounts;

        $str .= '<div id="cs-course-seats" >';
        $str .= html_writer::table($producttable);
        $str .= '</div>';

        return $str;
    }

    public function participant_row($participant = null) {
        global $CFG, $OUTPUT, $SITE, $PAGE;

        static $isuserstr;
        static $isnotuserstr;

        if (!isset($isuserstr)) {
            // Get strings once.
            $isuserstr = get_string('isuser', 'local_shop', $SITE->shortname);
            $isnotuserstr = get_string('isnotuser', 'local_shop', $SITE->shortname);
        }

        $this->check_context();

        $str = '';

        if ($participant) {

            $str .= '<tr>';
            $str .= '<td align="left">';
            $str .= @$participant->lastname;
            $str .= '</td>';
            $str .= '<td align="left">';
            $str .= @$participant->firstname;
            $str .= '</td>';
            $str .= '<td align="left">';
            $str .= @$participant->email;
            $str .= '</td>';
            $str .= '<td align="left">';
            $str .= strtoupper(@$participant->city);
            $str .= '</td>';
            if (!empty($this->theshop->endusermobilephonerequired)) {
                $str .= '<td align="left">';
                $str .= strtoupper(@$participant->phone2);
                $str .= '</td>';
            }
            if (!empty($this->theshop->enduserorganisationrequired)) {
                $str .= '<td align="left">';
                $str .= strtoupper(@$participant->institution);
                $str .= '</td>';
            }
            $str .= '<td align="left">';
            if (@$participant->moodleid) {
                if (file_exists($CFG->dirroot.'/theme/'.$PAGE->theme->name.'/favicon.jpg')) {
                    $str .= '<img src="'.$OUTPUT->pix_url('favicon').'" title="'.$isuserstr.'" />';
                } else {
                    $str .= '<img src="'.$OUTPUT->pix_url('i/moodle_host').'" title="'.$isuserstr.'" />';
                }
            } else {
                $str .= '<img src="'.$OUTPUT->pix_url('new', 'local_shop').'" title="'.$isnotuserstr.'" />';
            }
            $str .= '</td>';
            $str .= '<td align="right">';
            $str .= '<a title="'.get_string('deleteparticipant', 'local_shop').'"
                        href="Javascript:ajax_delete_user(\''.$CFG->wwwroot.'\', \''.$participant->email.'\')">';
            $str .= '<img src="'.$OUTPUT->pix_url('t/delete').'" />';
            $str .= '</a>';
            $str .= '</td>';
            $str .= '</tr>';
        } else {
            // Print a caption row.
            $str .= '<tr>';
            $str .= '<th align="left">';
            $str .= get_string('lastname');
            $str .= '</th>';
            $str .= '<th align="left">';
            $str .= get_string('firstname');
            $str .= '</th>';
            $str .= '<th align="left">';
            $str .= get_string('email');
            $str .= '</th>';
            $str .= '<th align="left">';
            $str .= get_string('city');
            $str .= '</th>';
            if (!empty($this->theshop->endusermobilephonerequired)) {
                $str .= '<th align="left">';
                $str .= get_string('phone2');
                $str .= '</th>';
            }
            if (!empty($this->theshop->enduserorganisationrequired)) {
                $str .= '<th align="left">';
                $str .= get_string('institution');
                $str .= '</th>';
            }
            $str .= '<th align="left">';
            $str .= get_string('moodleaccount', 'local_shop');
            $str .= '</th>';
            $str .= '<th align="right">';
            $str .= '</th>';
            $str .= '</tr>';
        }
        return $str;
    }

    public function participant_blankrow() {

        $this->check_context();

        static $i = 0;

        $str = '';

        $str .= '<tr>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="lastname_foo_'.$i.'" size="15" disabled="disabled" class="shop-disabled" />';
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="firstname_foo_'.$i.'" size="15" disabled="disabled" class="shop-disabled" />';
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="mail_foo_'.$i.'" size="20" disabled="disabled" class="shop-disabled" />';
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="city_foo_'.$i.'" size="14" disabled="disabled" class="shop-disabled" />';
        $str .= '</td>';
        if (!empty($this->theshop->endusermobilephonerequired)) {
            $str .= '<td align="left">';
            $str .= '<input type="text" name="phone2_foo_'.$i.'" size="13" disabled="disabled" class="shop-disabled" />';
            $str .= '</td>';
        }
        if (!empty($this->theshop->enduserorganisationrequired)) {
            $str .= '<td align="left">';
            $str .= '<input type="text" name="institution_foo_'.$i.'" size="13" disabled="disabled" class="shop-disabled" />';
            $str .= '</td>';
        }
        $str .= '<td align="left">';
        $str .= '</td>';
        $str .= '<td align="right">';
        $str .= '</td>';
        $str .= '</tr>';

        $i++;

        return $str;
    }

    public function new_participant_row() {
        global $CFG;

        $this->check_context();

        $str = '';

        $str .= '<form name="participant">';
        $str .= '<table width="100%">';
        $str .= '<tr>';
        $str .= '<td align="left">';
        $str .= get_string('lastname');
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= get_string('firstname');
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= get_string('email');
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= get_string('city');
        $str .= '</td>';
        if (!empty($this->theshop->endusermobilephonerequired)) {
            $str .= '<td align="left">';
            $str .= get_string('phone2');
            $str .= '</td>';
        }
        if (!empty($this->theshop->enduserorganisationrequired)) {
            $str .= '<td align="left">';
            $str .= get_string('institution');
            $str .= '</td>';
        }
        $str .= '<td align="right">';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="lastname" size="15" />';
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="firstname" size="15" />';
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="email" size="20" />';
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= '<input type="text" name="city" size="14" />';
        $str .= '</td>';
        if (!empty($this->theshop->endusermobilephonerequired)) {
            $str .= '<td align="left">';
            $str .= '<input type="text" name="phone2" size="13" maxlength="10" />';
            $str .= '</td>';
        }
        if (!empty($this->theshop->enduserorganisationrequired)) {
            $str .= '<td align="left">';
            $str .= '<input type="text" name="institution" size="15" size="15" maxlength="40" />';
            $str .= '</td>';
        }
        $str .= '<td align="right">';
        $str .= '<input type="button"
                        value="'.get_string('addparticipant', 'local_shop').'"
                        name="add_button"
                        onclick="ajax_add_user(\''.$CFG->wwwroot.'\', document.forms[\'participant\'])" />';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
        $str .= '</form>';

        return $str;
    }

    public function assignation_row($participant, $role) {
        global $CFG, $OUTPUT;

        $str = '';

        $str .= '<tr>';
        $str .= '<td align="left">';
        $str .= @$participant->lastname;
        $str .= '</td>';
        $str .= '<td align="left">';
        $str .= @$participant->firstname;
        $str .= '</td>';
        $str .= '<td align="right">';
        $str .= '<a href="Javascript:ajax_delete_assign(\''.$CFG->wwwroot.'\', \''.$role.'\', \''.$participant->email.'\')">';
        $str .= '<img src="'.$OUTPUT->pix_url('t/delete').'" /></a>';
        $str .= '</td>';
        $str .= '</tr>';

        return $str;
    }

    /**
     * prints a user selector for a product/role list from declared
     * participants removing already assigned people.
     */
    public function assignation_select($role) {
        global $SESSION, $CFG;

        $str = '';

        if (empty($SESSION->shopseats)) {
            return;
        }

        if (!empty($SESSION->shopseats->users[$role])) {
            $rkeys = array_keys($SESSION->shopseats->users[$role]);
        } else {
            $rkeys = array();
        }

        $options = array();
        if (!empty($SESSION->shopseats->participants)) {
            foreach ($SESSION->shopseats->participants as $email => $pt) {
                if (!in_array($email, $rkeys)) {
                    $options[$email] = $pt->lastname.' '.$pt->firstname;
                }
            }
        }
        $options = array('' => get_string('chooseparticipant', 'local_shop'));
        $attrs = array('onchange' => 'ajax_add_assign(\''.$CFG->wwwroot.'\', \''.$role.'\', this)');
        $str .= html_writer::select($options, 'addassign'.$role, '', $options, $attrs);

        return $str;
    }

    public function role_list($role) {
        global $OUTPUT, $SESSION;

        $this->check_context();

        $str = '';

        $roleassigns = @$SESSION->shopseats->users;

        $str .= $OUTPUT->heading(get_string(str_replace('_', '', $role), 'local_shop')); // Remove pseudo roles markers.
        if (!empty($roleassigns[$shortname][$role])) {
            $str .= '<div class="shop-role-list-container">';
            $str .= '<table width="100%" class="shop-role-list">';
            foreach ($roleassigns[$role] as $participant) {
                $str .= $this->assignation_row($participant, $role, true);
            }
            $str .= '</table>';
            $str .= '</div>';
        } else {
            $str .= '<div class="shop-role-list-container">';
            $str .= '<div class="shop-role-list">';
            $str .= get_string('noassignation', 'local_shop');
            $str .= '</div>';
            $str .= '</div>';
        }
        if (@$SESSION->shopseats->assigns < $SESSION->shopseats->seats) {
            $str .= $this->assignation_select($role, true);
        } else {
            $str .= get_string('seatscomplete', 'local_shop');
        }

        return $str;
    }

    public function send_button() {

        $assignstr = get_string('assignseats', 'block_shop_course_seats');

        $str = '';

        $str .= '<center>';
        $str .= '<input name="go_assign" value="'.$assignstr.'" type="submit">';
        $str .= '</center>';

        return $str;
    }

    public function get_required_javascript() {
        global $PAGE;

        $PAGE->requires->js('/blocks/shop_course_seats/js/js.js');
    }
}