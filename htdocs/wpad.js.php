<?php

/**
 * Javascript helper for WPAD.
 *
 * @category   apps
 * @package    wpad
 * @subpackage javascriptcontrollers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/wpad/
 */

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

clearos_load_language('firewall_custom');
clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "
$(document).ready(function() {
    $('#pac').css('min-height', '200px');
    $('#pac').css('font-family', 'monospace');
    $('#pac').css('font-size', '.9em');
    $('#proxy_location').change(function(event) {
        toggle_location();
    });
    $('#proxy_port').change(function(event) {
        toggle_port();
    });
    toggle_location();
    toggle_port();
});

function toggle_location() {
    if ($('#proxy_location').val() == 'custom') {
        $('#manual_location_field').show();
        if ($('#proxy_port').is(':visible') && $('#proxy_port').val() < 0) {
            $('#manual_port_field').show();
        }
        $('#proxy_port').val(3128);
        $('#proxy_port option[value=0]').attr('disabled','disabled');
    } else {
        $('#manual_location_field').hide();
        $('#proxy_port option[value=0]').removeAttr('disabled');
        if ($('#proxy_port').val() >= 0)
            $('#manual_port_field').hide();
    }
}

function toggle_port() {
    if ($('#proxy_port').val() < 0) {
        $('#manual_port_field').show();
    } else {
        $('#manual_port_field').hide();
    }
}

";

// vim: syntax=php ts=4
