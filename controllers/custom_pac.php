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

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Wpad Custom PAC controller.
 *
 * @category   apps
 * @package    wpad
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/wpad/
 */

class Custom_Pac extends ClearOS_Controller
{

    /**
     * Default controller for custom PAC generated file
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * Wpad edit controller
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    /**
     * Wpad view/edit controller
     *
     * @param string $mode mode
     *
     * @return view
     */

    function _view_edit($mode = NULL)
    {
        // Load dependencies
        //------------------

        $this->load->library('wpad/Wpad');
        $this->lang->load('wpad');

        $data = array(
            'mode' => $mode
        );

        $this->form_validation->set_policy('pac', 'wpad/Wpad', 'validate_pac_file', TRUE);
        $form_ok = $this->form_validation->run();

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->wpad->set_pac_file($this->input->post('pac'));
                redirect('/wpad');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------
        $data['pac'] = $this->wpad->get_pac_file();
        
        $this->page->view_form('wpad/custom_pac', $data, lang('wpad_app_name'));
    }
}
