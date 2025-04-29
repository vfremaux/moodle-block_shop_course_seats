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
 *
 * @module     block_shop_discounts
 * @package    blocks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// jshint unused: true, undef:true
define(['jquery', 'core/log', 'core/config'], function ($, log, cfg) {

    var blockshopdiscounts = {

        init: function() {
            $('.shop-discount-data').on('change', this.submit_discount_form);
            $('.shop-delete-code').on('click', this.clear_discount_code);
            log.debug("Shop discount block AMD initialized");
        },

        clear_discount_code: function(e) {
            e.preventDefault();
            e.stopPropagation();

            var that = $(this);
            var shopid = that.attr('data-shopid');
            var categoryid = that.attr('data-categoryid');
            var discountid = that.attr('data-discountid');

            var url = cfg.wwwroot + '/local/shop/front/view.php';
            url += '?view=shop';
            url += '&category=' + categoryid;
            url += '&shopid=' + shopid;
            url += '&what=cleardiscountcode';
            url += '&sesskey=' + cfg.sesskey;
            url += '&discountid=' + discountid;

            location.replace(url);
        },

        submit_discount_form: function() {
            var that = $(this);
            var id = that.attr('data-discountid');
            $('#shop-discount-' + id).submit();
        }

    };

    return blockshopdiscounts;
});
