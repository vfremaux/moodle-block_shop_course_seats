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
 * Parametrized JS for course_seats
 *
 * @package     block_shop_course_seats
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   2016 Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @todo : turn into AMD
 */

require('../../../config.php');
require_once($CFG->dirroot.'/blocks/shop_course_seats/locallib.php');
require_once($CFG->dirroot.'/local/shop/locallib.php');
require_once($CFG->dirroot.'/local/shop/classes/Shop.class.php');

use local_shop\Shop;

header("Content-type: text/javascript");
header("Cache-Control: No-cache");

$blockid = required_param('blockid', PARAM_INT);

$instance = $DB->get_record('block_instances', ['id' => $blockid]);
$theblock = block_instance('shop_course_seats', $instance);
$theshop = new Shop($theblock->config->shopinstance);

$context = context_system::instance();
$PAGE->set_context($context);

// Calculates and updates the seat count.
$requiredroles = array('student', '_supervisor');
$assigned = shop_course_seats_check_assigned_seats($requiredroles);
$unassignedstr = get_string('notallassigned', 'local_shop');
?>

// this early loads from server
var assigned = '<?php echo $assigned; ?>';

function ajax_add_user(wwwroot, formobj) {
    urlbase = wwwroot+'/blocks/shop_course_seats/ajax/service.php';
    ajax_waiter = '<div class="ajax-waiter"><center><img src="'+wwwroot+'/local/shop/pix/loading29.gif" /></center></div>';

    // kind a very simple serialize/unserialize
    rolelist = '<?php echo implode(',', $requiredroles); ?>';
    roles = rolelist.split(',');

    pt = new Object();
    pt.lastname = formobj.lastname.value;
    pt.firstname = formobj.firstname.value;
    pt.email = formobj.email.value;
    pt.city = formobj.city.value;
<?php
if (!empty($theshop->enduserorganisationrequired)) {
?>
        pt.institution = formobj.institution.value;
<?php
}
if (!empty($theshop->endusermobilephonerequired)) {
?>
        pt.phone2 = formobj.phone2.value;
<?php } ?>

    $('#participantlist').html(ajax_waiter);

    $.post(
        urlbase, 
        {
            id: '<?php echo $theshop->id ?>',
            blockid: '<?php echo $blockid ?>',
            action: 'addparticipant',
            participant: JSON.stringify(pt),
            roles: JSON.stringify(roles)
        },
        function(data, status) {
            $('#participantlist').html(data);
            formobj.lastname.value = '';
            formobj.firstname.value = '';
            formobj.email.value = '';
            // Keep city and institution values to speed up input
            // formobj.city.value = '';
<?php if (!empty($theshop->enduserorganisationrequired)) { ?>
            // formobj.institution.value = '';
<?php
}
if (!empty($theshop->endusermobilephonerequired)) {
?>
            formobj.phone2.value = '';
<?php } ?>

            for (i = 0; i < roles.length; i++) {
                $('#'+roles[i]+'list').html(ajax_waiter);
            }

            $.post(
                urlbase, 
                {
                    id: '<?php echo $theshop->id ?>',
                    blockid: '<?php echo $blockid ?>',
                    action: 'assignalllistobj',
                },
                function(data, status) {
                    obj = JSON.parse(data);
                    obj.content;
                    for (i = 0; i < roles.length; i++) {
                        r = roles[i];
                        html = obj.content[r];
                        $('#'+r+'list'+p).html(html);
                    }
                }
            );
        }
    );
}


function ajax_delete_user(wwwroot, ptmail) {

    urlbase = wwwroot+'/blocks/shop_course_seats/ajax/service.php';
    ajax_waiter = '<div class="ajax-waiter"><center><img src="'+wwwroot+'/local/shop/pix/loading29.gif" /></center></div>';

    // kind a very simple serialize/unserialize
    rolelist = '<?php echo implode(',', $requiredroles); ?>';
    roles = rolelist.split(',');

    $('#participantlist').html(ajax_waiter);

    $.post(urlbase, 
        {
            id: '<?php echo $theshop->id ?>',
            blockid: '<?php echo $blockid ?>',
            action: 'deleteparticipant',
            participantid: ptmail,
            roles: JSON.stringify(roles)
        },
        function(data, status) {
            $('#participantlist').html(data);

            for (i = 0; i < roles.length; i++) {
                $('#'+roles[i]+'list').html(ajax_waiter);
            }

            $.post(
                urlbase, 
                {
                    id: '<?php echo $theshop->id ?>',
                    blockid: '<?php echo $blockid ?>',
                    action: 'assignalllistobj',
                },
                function(data, status) {
                    obj = JSON.parse(data);
                    obj.content;
                    for (i = 0; i < roles.length; i++) {
                        r = roles[i];
                        html = obj.content[r];
                        $('#'+r+'list').html(html);
                    }
                }
            );
        }
    );
}

function ajax_add_assign(wwwroot, assignrole, selectobj) {

    urlbase = wwwroot+'/blocks/shop_course_seats/ajax/service.php';
    ajax_waiter = '<div class="ajax-waiter"><center><img src="'+wwwroot+'/local/shop/pix/loading29.gif" /></center></div>';

    requiredroles = JSON.parse('<?php echo json_encode($requiredroles); ?>');

    for (rix in requiredroles) {
        role = requiredroles[rix];
        $('#'+role+'list'+product).html(ajax_waiter);
    }

    $.post(
        urlbase, 
        {
            id: '<?php echo $theshop->id ?>',
            blockid: '<?php echo $blockid ?>',
            action: 'addassign',
            role:assignrole,
            participantid: selectobj.options[selectobj.selectedIndex].value
        },
        function(data,status) {
            rolestubs = JSON.parse(data);
            for (rix in requiredroles) {
                role = requiredroles[rix];
                $('#'+role+'list').html(rolestubs.content[role]);
            }
            
            // this need be done on positive return or we might unsync
            assigned++;
            if (assigned < required) {
                $('#next-button').css('opacity', '0.5');
                $('#next-button').removeClass('shop-active-button');
                $('#next-button').attr('disabled', 'disabled');
                $('#next-button').attr('title', '<?php echo str_replace("'", '\\\'', $unassignedstr) ?>');
            } else {
                $('#next-button').css('opacity', '1.0');
                $('#next-button').addClass('shop-active-button');
                $('#next-button').attr('disabled', null);
                $('#next-button').attr('title', '<?php print_string('continue', 'local_shop') ?>');
            }
        }
    );
}

function ajax_delete_assign(wwwroot, assignrole, email) {
    urlbase = wwwroot+'/blocks/shop_course_seats/ajax/service.php';
    ajax_waiter = '<div class="ajax-waiter"><center>'+
        '<img src="'+wwwroot+'/local/shop/pix/loading29.gif" /><center></div>';

    requiredroles = JSON.parse('<?php echo json_encode($requiredroles); ?>');

    for (rix in requiredroles) {
        role = requiredroles[rix];
        $('#'+role+'list').html(ajax_waiter);
    }

    $.post(
        urlbase,
        {
            id: '<?php echo $theshop->id ?>',
            blockid: '<?php echo $blockid ?>',
            action: 'deleteassign',
            role: assignrole,
            participantid: email
        },
        function(data, status) {
            rolestubs = JSON.parse(data);
            for (rix in requiredroles) {
                role = requiredroles[rix];
                $('#'+role+'list'+product).html(rolestubs.content[role]);
            }
            assigned--;
            if (assigned < 0) assigned = 0; // security, should not happen
            if (assigned < required) {
                $('#next-button').css('opacity', '0.5');
                $('#next-button').removeClass('shop-active-button');
                $('#next-button').attr('disabled', 'disabled');
                $('#next-button').attr('title', '<?php echo str_replace("'", '\\\'', $unassignedstr) ?>');
            } else {
                $('#next-button').css('opacity', '1.0');
                $('#next-button').addClass('shop-active-button');
                $('#next-button').attr('disabled', null);
                $('#next-button').attr('title', '<?php print_string('continue', 'local_shop') ?>');
            }
        }
    );
}
