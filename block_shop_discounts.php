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
 * Main block class
 *
 * @package    block_shop_discounts
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (activeprolearn.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/shop/lib.php');
require_once($CFG->dirroot.'/local/shop/locallib.php');
require_once($CFG->dirroot.'/local/shop/classes/Shop.class.php');
require_once($CFG->dirroot.'/local/shop/classes/Catalog.class.php');
require_once($CFG->dirroot.'/local/shop/pro/classes/Discount.class.php');

use local_shop\Shop;
use local_shop\Catalog;
use local_shop\Category;
use local_shop\Discount;

/**
 * Main block class
 */
class block_shop_discounts extends block_base {

    /**
     * Standard init
     */
    public function init() {
        $this->title = get_string('discounts', 'block_shop_discounts');
    }

    /**
     * Instance specialization
     */
    public function specialization() {
        $this->title = '';
    }

    /**
     * Applicable formats
     */
    public function applicable_formats() {
        return ['all' => true, 'my' => true, 'course' => true];
    }

    /**
     * Allow multiple instances in context
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Allow instance configuration ?
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Get all discounts that have somethin to say (arguments and/or interactive forms
     * display a vertical list (in order) of discount arguments and formlets.
     */
    public function get_content() {
        global $OUTPUT, $SESSION;

        if ($this->content !== null) {
            return $this->content;
        }

        list($theshop, $thecatalog, $theblock) = shop_build_context();
        $view = optional_param('view', 'shop', PARAM_ALPHA);

        $catid = optional_param('category', 0, PARAM_INT);
        $catalias = optional_param('categoryalias', '', PARAM_ALPHANUM);
        if (empty($catid) && !empty($catalias)) {
            $category = Category::instance_by_seoalias($catalias);
        }

        $this->content = new stdClass;

        $template = new StdClass();
        $template->shopid = $theshop->id;
        $template->catgoryid = $catid;
        $template->view = $view;
        $template->sesskey = sesskey();
        $template->title = get_string('discounts', 'block_shop_discounts');

        $discounts = Discount::get_applicable_discounts($theshop->id);
        if ($discounts) {
            foreach ($discounts as $di) {
                $discounttpl = $di->interactive_form();
                if (method_exists($di, 'interactive_form_return')) {
                    $discounttpl->value = $di->interactive_form_return($_REQUEST); // Its safe to pass this in if discount classes sanitize the reception.
                }
                $template->discounts[] = $discounttpl;
            }
        }

        $this->content->text = $OUTPUT->render_from_template('block_shop_discounts/discounts', $template);

        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Default behaviour : give raw discount record.
     */
    public function export_for_template() {
        return $this->record;
    }

    /**
     * Hide the title bar when none set.
     */
    public function hide_header() {

        list($theshop, $thecatalog, $theblock) = shop_build_context();
        $discounts = Discount::get_applicable_discounts($theshop->id);
        $hasnodiscountsprintable = true;
        if ($discounts) {
            foreach ($discounts as $di) {
                if (!empty($di->argument)) {
                    $hasnodiscountsprintable = false;
                    break;
                }
                if ($di->is_interactive_eligible()) {
                    $hasnodiscountsprintable = false;
                    break;
                }
            }
        }
        return (!empty($this->config->hidetitle)) || $hasnodiscountsprintable;
    }

    /**
     * Get javascript is block is instanciated in UI
     */
    public function get_required_javascript() {

        $this->page->requires->js_call_amd('block_shop_discounts/shop_discounts', 'init');
    }
}
