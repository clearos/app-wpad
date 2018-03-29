
<?php

/**
 * WPAD controller.
 *
 * @category   apps
 * @package    wpad
 * @subpackage views
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('wpad');

///////////////////////////////////////////////////////////////////////////////
// Warnings
///////////////////////////////////////////////////////////////////////////////
if ($proxy_location == 'self' && !$proxy_server_installed)
    echo infobox_warning(
        lang('base_warning'), lang('wpad_proxy_server_not_installed') .
        "<div style='text-align: center; padding-top: 5px;'>" . anchor_custom('/app/marketplace/view/web_proxy', lang('wpad_install_web_proxy')) . "</div>"
    );
else if ($proxy_location == 'self' && !$proxy_server_running)
    echo infobox_warning(
        lang('base_warning'), lang('wpad_proxy_server_not_running') .
        "<div style='text-align: center; padding-top: 5px;'>" . anchor_custom('/app/web_proxy', lang('wpad_configure_web_proxy')) . "</div>"
    );

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('wpad/auto_pac/edit');
echo form_header(lang('wpad_pac_configuration'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/wpad')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/wpad/auto_pac/edit')
    );
}

echo field_dropdown('proxy_location', $proxy_location_options, $proxy_location, lang('wpad_proxy_location'), $read_only, array('id' => 'proxy_location'));
echo field_input('proxy_location_manual', $proxy_location_manual, lang('wpad_proxy_ip_address'), $read_only, array('id' => 'manual_location', 'hide_field' => TRUE));
echo field_dropdown('proxy_port', $proxy_port_options, $proxy_port, lang('wpad_proxy_port'), $read_only, array('id' => 'proxy_port'));
echo field_input('proxy_port_manual', $proxy_port_manual, lang('wpad_proxy_port_number'), $read_only, array('id' => 'manual_port', 'hide_field' => TRUE));
foreach ($lan_list as $interface => $lan)
    echo field_dropdown('lan[' . $interface . ']', $rule_options, $lan_rules[$interface], lang('wpad_subnet') . ' ' . $lan['network'] . '/' . $lan['netmask'], $read_only);
echo field_multiselect_dropdown('exempt[]', $devices, $exempt, lang('wpad_exempt_from_proxy'), FALSE, $read_only);
echo field_dropdown('default_rule', $rule_options, $default_rule, lang('wpad_default_rule'), $read_only, array('id' => 'default_rule'));

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
