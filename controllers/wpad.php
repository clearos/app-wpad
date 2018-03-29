<?php

/**
 * WPAD controller.
 *
 * @category   apps
 * @package    wpad
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/wpad/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\wpad\Wpad as Wpad_Class;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * WPAD controller.
 *
 * @category   apps
 * @package    wpad
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/wpad/
 */

class Wpad extends ClearOS_Controller
{

    /**
     * Wpad default controller
     *
     * @return view
     */

    function index()
    {

        // Load dependencies
        //------------------

        $this->load->library('wpad/Wpad');
        $this->lang->load('wpad');

        // Load views
        //-----------

        $views = array(
            'wpad/settings', 'wpad/custom_pac'
        );

        //if ($this->wpad->get_custom_pac())
        //    $views[] = 'wpad/custom_pac';
        //else
        //    $views[] = 'wpad/auto_pac';

        $this->page->view_forms($views, lang('wpad_app_name'));
    }

    /**
     * Start Web Server controller
     *
     * @return view
     */

    function httpd_start()
    {

        // Load dependencies
        //------------------

        $this->load->library('web_server/Httpd');

        try {
            $this->httpd->set_running_state(TRUE);
            $this->httpd->set_boot_state(TRUE);
            if ($this->httpd->get_running_state() != TRUE)
                $this->page->set_message(lang('wpad_unable_start_httpd'), 'warning');
        } catch (Exception $e) {
            $this->page->set_message(clearos_exception_message($e), 'warning');
        }

        redirect('wpad');
    }
}
