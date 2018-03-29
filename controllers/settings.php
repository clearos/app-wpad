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
 * Settings controller.
 *
 * @package    wpad
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/wpad/
 */

class Settings extends ClearOS_Controller
{

    /**
     * Wpad default controller
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

        $data['mode'] = $mode;

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('enabled', 'wpad/Wpad', 'validate_enabled', TRUE);
        $this->form_validation->set_policy('hostname', 'wpad/Wpad', 'validate_hostname', TRUE);
        //$this->form_validation->set_policy('dns', 'wpad/Wpad', 'validate_dns', FALSE);
        //$this->form_validation->set_policy('dhcp[]', 'wpad/Wpad', 'validate_dhcp', FALSE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->wpad->set_hostname($this->input->post('hostname'));
                $this->wpad->set_enabled($this->input->post('enabled'));
                //$this->wpad->set_dns($this->input->post('dns'));
                //$dhcp_defn = $this->input->post('dhcp');
                //foreach ($dhcp_defn as $iface => $enabled) {
                //    $interface = base64_decode(str_pad(strtr($iface, '-_', '+/'), strlen($iface) % 4, '=', STR_PAD_RIGHT));
                //    $this->wpad->set_dhcp($interface, $enabled);
                //}
                //$this->wpad->set_custom_pac($this->input->post('custom_pac'));
                redirect('/wpad');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['enabled'] = $this->wpad->get_enabled();
            $data['hostname'] = $this->wpad->get_hostname();
            //$data['dns'] = $this->wpad->get_dns();
            $data['dhcp'] = $this->wpad->get_dhcp();
            //$data['custom_pac'] = $this->wpad->get_custom_pac();
            // If WPAD is enabled, check Apache server
            if ($data['enabled']) {
                $data['web_server_installed'] = (clearos_library_installed('web_server/Httpd')) ? TRUE : FALSE;
                if (clearos_library_installed('web_server/Httpd')) {
                    $this->load->library('web_server/Httpd');
                    $data['web_server_running'] = $this->httpd->get_running_state();
                }
            }
            if (clearos_library_installed('dhcp/Dnsmasq')) {
                $this->load->library('dhcp/Dnsmasq');

                try {
                    $subnets = $this->dnsmasq->get_subnets();
                    foreach ($subnets as $interface => $subnet) {
                        $data['subnets'][$interface] = $this->dnsmasq->get_subnet($interface);
                        $data['subnets'][$interface]['isconfigured'] = $subnet['isconfigured'];
                    }
                } catch (Exception $e) {
                    $this->page->view_exception($e);
                    return;
                }
            }
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('wpad/settings', $data, lang('wpad_app_name'));
    }
}
