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
 * Capability definitions for the inwicast module.
 *
 * @package    block_shop_course_seats
 * @category   blocks
 * @copyright  2013 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function block_shop_course_seats_get_products($context, $userid = 0) {
    global $USER, $DB, $COURSE;

    $params = array($COURSE->id);
    $userclause = '';
    if ($userid) {
        $userclause = " AND c.hasaccount = ? ";
        $params[] = $userid;
    }

    $sql = "
        SELECT DISTINCT
            cp.id,
            ci.renewable,
            ci.name,
            ci.code,
            cp.reference,
            cp.currentbillitemid,
            cp.contexttype,
            cp.instanceid,
            cp.startdate,
            cp.enddate,
            cp.productiondata
        FROM
            {local_shop_catalogitem} ci,
            {local_shop_productevent} pe,
            {local_shop_billitem} bi,
            {local_shop_customer} c,
            {local_shop_product} cp
        LEFT JOIN
            {user_enrolments} ue
        ON
            ue.id = cp.instanceid
        LEFT JOIN
            {enrol} e
        ON
            e.id = ue.enrolid
        WHERE
            cp.catalogitemid = ci.id AND
            cp.id = pe.productid AND
            bi.id = pe.billitemid AND
            cp.customerid = c.id AND
            cp.contexttype = 'user_enrolment' AND
            (e.courseid IS NULL or (e.courseid = ?))
            $userclause
        ORDER BY
            cp.startdate DESC
    ";

    return $DB->get_records_sql($sql, $params);
}

/**
 * checks purchased products and quantities and calculates the neaded amount of seats.
 * We need check in catalog definition id product is seat driven or not. If seat driven
 * the quantity adds to seat couts. If not, 1 seat is added to the seat count.
 */
function shop_course_seats_check_assigned_seats($requiredroles) {
    global $SESSION;

    $assigned = 0;

    if (!isset($SESSION->shopseats)) return 0;

    if ($requiredroles && !empty($SESSION->shopseats->users)) {
        foreach ($SESSION->shopseats->users as $product => $roleassigns) {
            foreach ($roleassigns as $role => $participants) {
                $assigned += count($participants);
            }
        }
    }

    return $assigned;
}
