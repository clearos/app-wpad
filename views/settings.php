
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
$this->lang->load('dns');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Warnings
///////////////////////////////////////////////////////////////////////////////

if (isset($web_server_installed) && !$web_server_installed)
    echo infobox_warning(
        lang('base_warning'), lang('wpad_web_server_not_installed') .
        "<div style='text-align: center; padding-top: 5px;'>" . anchor_custom('/app/marketplace/view/web_server', lang('wpad_install_web_server')) . "</div>"
    );
else if (isset($web_server_running) && !$web_server_running)
    echo infobox_warning(
        lang('base_warning'), lang('wpad_web_server_not_running') .
        "<div style='text-align: center; padding-top: 5px;'>" . anchor_custom('/app/wpad/httpd_start', lang('wpad_start_service')) . "</div>"
    );
    
///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('wpad/settings/edit');
echo form_header(lang('base_settings'));

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
        anchor_edit('/app/wpad/settings/edit')
    );
}

echo field_toggle_enable_disable('enabled', $enabled, lang('wpad_enabled'), $read_only);

echo field_input('hostname', $hostname, lang('network_hostname'), $read_only);
// DNS
/*
if (clearos_app_installed('dns')) {
    echo field_toggle_enable_disable('dns', $dns, lang('wpad_dns'), $read_only);
} else {
    echo field_info(
        'dns',
        lang('wpad_dns'),
        lang('wpad_dns_not_installed') . ' ' . anchor_custom('/app/marketplace/view/dns',
        lang('base_install'))
    );
}
*/

// DHCP
if (clearos_app_installed('dhcp')) {
    foreach ($subnets as $interface => $subnet) {
        $key = rtrim(strtr(base64_encode($interface), '+/', '-_'), '=');
        $label = lang('wpad_dhcp') . ', ' . $interface .  ' (' . $subnet['network'] . ')';
        if ($subnet['isconfigured'])
            echo field_toggle_enable_disable(
                "dhcp[$key]",
                $subnet['wpad'] != NULL ? TRUE : FALSE,
                $label,
                TRUE
            );
        else
            echo field_info(
                "dhcp[$key]",
                $label,
                lang('wpad_dhcp_not_configured') . ' - ' . anchor_configure('/app/dhcp', 'link-only')
            );
    }
} else {
    echo field_info(
        'dhcp',
        lang('wpad_dhcp'),
        lang('wpad_dhcp_not_installed') . ' ' . anchor_custom('/app/marketplace/view/dhcp',
        lang('base_install'))
    );
}

/*
$pac_options = array(
    0 => lang('wpad_autogenerate'),
    1 => lang('wpad_custom')
);

// WPAD
echo field_dropdown('custom_pac', $pac_options, $custom_pac, lang('wpad_pac_file'), $read_only);
*/

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
