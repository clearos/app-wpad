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
 * Wpad Auto PAC controller.
 *
 * @category   apps
 * @package    wpad
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/wpad/
 */

class Auto_Pac extends ClearOS_Controller
{

    /**
     * Default controller for auto PAC generated file
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

        $this->form_validation->set_policy('proxy_location', 'wpad/Wpad', 'validate_proxy_location', TRUE);
        $this->form_validation->set_policy('proxy_port', 'wpad/Wpad', 'validate_proxy_port', TRUE);
        if ($this->input->post('proxy_location') == 'custom')
            $this->form_validation->set_policy('proxy_location_manual', 'wpad/Wpad', 'validate_proxy_ip', TRUE);
        if ($this->input->post('proxy_port') == -1)
            $this->form_validation->set_policy('proxy_port_manual', 'wpad/Wpad', 'validate_proxy_port', TRUE);
        $this->form_validation->set_policy('exempt', 'wpad/Wpad', 'validate_exemption_list', FALSE);
        $this->form_validation->set_policy('default_rule', 'wpad/Wpad', 'validate_rule', TRUE);
        $form_ok = $this->form_validation->run();

        if ($form_ok) {
            try {
                $this->wpad->set_proxy_location($this->input->post('proxy_location'));
                $this->wpad->set_proxy_port($this->input->post('proxy_port'));
                if ($this->input->post('proxy_location') == 'custom')
                    $this->wpad->set_proxy_location_manual($this->input->post('proxy_location_manual'));
                if ($this->input->post('proxy_port') == -1)
                    $this->wpad->set_proxy_port_manual($this->input->post('proxy_port_manual'));
                foreach ($this->input->post('lan') as $interface => $action)
                    $this->wpad->set_rule($interface, $action);
        
                $this->wpad->set_exemption_list($this->input->post('exempt'));
                $this->wpad->set_rule('default', $this->input->post('default_rule'));
                redirect('/wpad');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------
        $data['proxy_location'] = $this->wpad->get_proxy_location();
        $data['proxy_location_manual'] = $this->wpad->get_proxy_location_manual();
        $data['proxy_location_options'] = $this->wpad->get_proxy_location_options();
        $data['proxy_port'] = $this->wpad->get_proxy_port();
        $data['proxy_port_manual'] = $this->wpad->get_proxy_port_manual();
        $data['proxy_port_options'] = $this->wpad->get_proxy_port_options();
        $data['default_rule'] = $this->wpad->get_rule();
        $data['rule_options'] = $this->wpad->get_rule_options();

        $data['lan_list'] = $this->wpad->get_interfaces();
        foreach ($data['lan_list'] as $interface => $subnet)
            $data['lan_rules'][$interface] = $this->wpad->get_rule($interface);

        $devices = $this->wpad->get_device_list();
        $data['exempt'] = $this->wpad->get_exemption_list();
        foreach ($devices as $mac => $info) {
            $dev_id = key($info['mapping']) . ' (' . lang('network_map_unmapped') . ')';
            if (isset($info['nickname']))
                $dev_id = $info['nickname']; 
            else if (isset($info['username']))
                $dev_id = $info['username'] . ' - ' . $device['type']; 
            $data['devices'][key($info['mapping'])] = $dev_id;
        }

        // If proxy is set to self, check for software/running service 
        if (clearos_library_installed('web_proxy/Squid')) {
            $data['proxy_server_installed'] = TRUE;
            $this->load->library('web_proxy/Squid');
            $data['proxy_server_running'] = $this->squid->get_running_state();
        } else {
            $data['proxy_server_installed'] = FALSE;
        }

        $this->page->view_form('wpad/auto_pac', $data, lang('wpad_app_name'));
    }
}
