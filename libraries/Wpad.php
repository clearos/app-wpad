<?php

/**
 * WPAD controller.
 *
 * @category   apps
 * @package    wpad
 * @subpackage libaries
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
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\wpad;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('wpad');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\dhcp\Dnsmasq as Dnsmasq;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\content_filter\DansGuardian as DansGuardian;
use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Hosts as Hosts;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\network\Role as Role;
use \clearos\apps\network_map\Network_Map as Network_Map;
use \clearos\apps\web_proxy\Squid as Squid;
use \clearos\apps\web_server\Httpd as Httpd;

clearos_load_library('base/Configuration_File');
clearos_load_library('dhcp/Dnsmasq');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('content_filter/DansGuardian');
clearos_load_library('firewall/Firewall');
clearos_load_library('network/Hostname');
clearos_load_library('network/Hosts');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network_Utils');
clearos_load_library('network/Role');
clearos_load_library('network_map/Network_Map');
clearos_load_library('web_proxy/Squid');
clearos_load_library('web_server/Httpd');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Wpad class.
 *
 * @category   apps
 * @package    wpad
 * @subpackage libaries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2018 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/wpad/
 */

class Wpad extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_DAT = "/var/clearos/wpad/wpad.dat";
    const FILE_APACHE_CONFIGLET = "/etc/httpd/conf.d/wpad.conf";
    const FILE_CONFIG = "/etc/clearos/wpad.conf";
    const FILE_FIREWALL_D = "/etc/clearos/firewall.d/10-wpad";
    const ALL_LAN = "all_lan";

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $config = NULL;
    protected $is_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Wpad constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Set the wpad enable status.
     *
     * @param boolean $enabled set WPAD on/off
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_enabled($enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_DAT, TRUE); 
        $apache_configlet = new File(self::FILE_APACHE_CONFIGLET, TRUE); 
        $httpd_reset = FALSE;
        if ($enabled) {
            if (!$file->exists()) {
                // Let's check for a backup configuration
                $backup = new File(self::FILE_DAT . '.bak', TRUE); 
                if ($backup->exists())
                    $backup->move_to(self::FILE_DAT);
                else
                    $file->create('root', 'root', '0644');
            }
            if ($apache_configlet->exists())
                $apache_configlet->delete();
            $template = new File(clearos_app_base('wpad') . "/deploy/wpad.conf");
            if (!$template->exists())
                throw new Engine_Exception(lang('wpad_template_not_found'), CLEAROS_ERROR);
            $template->copy_to(self::FILE_APACHE_CONFIGLET);
            $hostname = new Hostname();
            $hosts = new Hosts();
            $apache_configlet->replace_lines("/ServerName SERVER_NAME/", "    ServerName " . $this->get_hostname() . "\n");
            $httpd_reset = TRUE;
        } else {
            if ($file->exists()) {
                $backup = new File(self::FILE_DAT . '.bak', TRUE); 
                if ($backup->exists())
                    $backup->move_to(self::FILE_DAT .'.' . date('ymd-hi') . '.bak');
                $file->move_to(self::FILE_DAT . '.bak');
            }
            if ($apache_configlet->exists()) {
                $apache_configlet->delete();
                $httpd_reset = TRUE;
            }
        }
        if ($httpd_reset && clearos_library_installed('web_server/Httpd')) {
            $httpd = new Httpd();
            $httpd->reset(TRUE);
        }
    }

    /**
     * Set the wpad DNS service.
     *
     * @param boolean $enabled set DNS on/off
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_dns($enabled)
    {
        clearos_profile(__METHOD__, __LINE__);
        $hostname = new Hostname();
        $hosts = new Hosts();
        $interfaces = $this->get_interfaces(FALSE);
        $wpad_dns = 'wpad.' . $hostname->get();
        foreach ($interfaces as $iface => $ifdata) {
            if ($enabled) {
                if ($hosts->entry_exists($ifdata['ip'])) {
                    $entry = $hosts->get_entry($ifdata['ip']);
                    if ($entry['hostname'] != $wpad_dns && !in_array($wpad_dns, $entry['aliases'])) {
                        $entry['aliases'][] = $wpad_dns;
                        $entry = $hosts->edit_entry($entry['ip'], $entry['hostname'], $entry['aliases']);
                    }
                } else {
                    $hosts->add_entry($ifdata['ip'], $wpad_dns);
                }
            } else {
                if ($hosts->entry_exists($ifdata['ip'])) {
                    $entry = $hosts->get_entry($ifdata['ip']);
                    if ($entry['hostname'] == $wpad_dns && empty($entry['aliases'])) {
                        $hosts->delete_entry($ifdata['ip']);
                    } else if ($entry['hostname'] == $wpad_dns) {
                        $hostname = $entry['aliases'][0];
                        $aliases = array_shift($entry['aliases']);
                        $entry = $hosts->edit_entry($ifdata['ip'], $hostname, $aliases);
                    } else {
                        foreach ($entry['aliases'] as $key => $alias) {
                            if ($alias == $wpad_dns)
                                unset($entry['aliases'][$key]);
                        }
                        $entry = $hosts->edit_entry($ifdata['ip'], $entry['hostname'], $entry['aliases']);
                    }
                }
            }
        }
    }

    /**
     * Set the wpad DHCP service.
     *
     * @param string  $interface interface
     * @param boolean $enabled   set DHCP on/off
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_dhcp($interface, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!clearos_library_installed('dhcp/Dnsmasq'))
            return;

        clearos_load_library('dhcp/Dnsmasq');
        $dnsmasq = new Dnsmasq();
        $subnet = $dnsmasq->get_subnet($interface);
        
        // Don't bother touching config if WPAD is set
        if ($enabled && isset($subnet['wpad']) && $subnet['wpad'] != '')
            return;
        // Don't bother touching config if WPAD is not set
        if (!$enabled && (!isset($subnet['wpad']) || $subnet['wpad'] == ''))
            return;
        
        $wpad = '';
        if ($enabled) {
            $hostname = new Hostname();
            $wpad = "http://wpad." . $hostname->get() . "/wpad.dat";
        }
        // Update req'd
        $dnsmasq->update_subnet(
            $interface,
            (isset($subnet['start'])) ? $subnet['start'] : '',
            (isset($subnet['end'])) ? $subnet['end'] : '',
            (isset($subnet['lease_time'])) ? $subnet['lease_time'] : '',
            (isset($subnet['gateway'])) ? $subnet['gateway'] : '',
            (isset($subnet['dns'])) ? $subnet['dns'] : '',
            (isset($subnet['wins'])) ? $subnet['wins'] : '',
            (isset($subnet['tftp'])) ? $subnet['tftp'] : '',
            (isset($subnet['ntp'])) ? $subnet['ntp'] : '',
            $wpad
        );
    }

    /**
     * Set hostname.
     *
     * @param String $hostname
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_hostname($hostname));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('hostname', $hostname);
    }

    /**
     * Set custom PAC file.
     *
     * @param boolean $custom use custom WPAD configuration
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_custom_pac($custom)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_custom($custom));

        if (! $this->is_loaded)
            $this->_load_config();

        if ($custom === 'on' || $custom == 1 || $custom == TRUE) {
            $custom = 1;
        } else {
            $custom = 0;
            $this->auto_generate_pac(TRUE);
        }

        $this->_set_parameter('custom-pac', $custom);
    }

    /**
     * Set proxy location.
     *
     * @param string $location proxy location
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_proxy_location($location)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_proxy_location($location));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('proxy-location', $location);
    }

    /**
     * Set proxy location (manual override).
     *
     * @param string $location proxy location
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_proxy_location_manual($location)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_proxy_location($location));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('proxy-location-manual', $location);
    }

    /**
     * Set proxy port.
     *
     * @param int $port proxy port
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_proxy_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_proxy_port($port));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('proxy-port', $port);
    }

    /**
     * Set default rule.
     *
     * @param string $id     Interface or 'default'
     * @param string $action Action to take
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_rule($id, $action)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_rule($action));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('rule-' . $id, $action);
    }

    /**
     * Set proxy port.
     *
     * @param int $port proxy port
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_proxy_port_manual($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_proxy_port($port));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('proxy-port-manual', $port);
    }

    /**
     * Set PAC file.
     *
     * @param string $pac PAC file contents
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_pac_file($pac)
    {
        clearos_profile(__METHOD__, __LINE__);
        $file = new File(self::FILE_DAT, TRUE);
        if ($file->exists())
            $file->delete();
        $file->create('root', 'root', '0644', TRUE);

        $file->add_lines($pac);
    }

    /**
     * Set exemption list.
     *
     * @param array $exempt exemption list
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_exemption_list($exempt)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_exemption_list($exempt));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('exemption-list', json_encode($exempt));
    }

    /**
     * Returns status of wpad.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function get_enabled()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $file = new File(self::FILE_DAT, TRUE);
        if ($file->exists())
            return TRUE;

        return FALSE;
    }

    /**
     * Returns status of DNS.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function get_dns()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $hostname = new Hostname();
        $hosts = new Hosts();
        $interfaces = $this->get_interfaces(FALSE);
        $wpad_dns = 'wpad.' . $hostname->get();
        foreach ($interfaces as $iface => $ifdata) {
            if ($hosts->entry_exists($ifdata['ip'])) {
                $entry = $hosts->get_entry($ifdata['ip']);
                if ($entry['hostname'] == $wpad_dns || in_array($wpad_dns, $entry['aliases']))
                    return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Returns status of DHCP.
     *
     * @param string $interface interface
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function get_dhcp($interface)
    {
        clearos_profile(__METHOD__, __LINE__);
        if (!clearos_library_installed('dhcp/Dnsmasq'))
            return FALSE;
            
        clearos_load_library('dhcp/Dnsmasq');
        $dnsmasq = new Dnsmasq();
        $subnet = $dnsmasq->get_subnet($interface);
        
        if (isset($subnet['wpad']) && $subnet['wpad'] != '')
            return TRUE;

        return FALSE;
    }

    /**
     * Get hostname.
     *
     * @return String
     * @throws Engine_Exception
     */

    public function get_hostname()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $hostname = new Hostname();
        $wpad_hostname = $this->config['hostname'];
        if (empty($wpad_hostname))
            return "wpad." . $hostname->get();

        return $wpad_hostname;
    }

    /**
     * Get custom PAC file.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    public function get_custom_pac()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $custom = $this->config['custom-pac'];
        if ($custom == NULL || !$custom)
            return FALSE;

        return (boolean)$custom;
    }

    /**
     * Get custom PAC file.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    public function get_pac_file()
    {
        clearos_profile(__METHOD__, __LINE__);
        $file = new File(self::FILE_DAT, TRUE);
        if (!$file->exists())
            return '';
        return $file->get_contents();
    }

    /**
     * Get proxy location.
     *
     * @return string
     * @throws Engine_Exception
     */

    public function get_proxy_location()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $location = $this->config['proxy-location'];
        if ($location == NULL || !$location)
            return self::ALL_LAN;

        return $location;
    }

    /**
     * Get proxy location (manual override).
     *
     * @return string
     * @throws Engine_Exception
     */

    public function get_proxy_location_manual()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $location = $this->config['proxy-location-manual'];
        if ($location == NULL || !$location)
            return '';

        return $location;
    }

    /**
     * Get proxy port (manual override).
     *
     * @return int
     * @throws Engine_Exception
     */

    public function get_proxy_port_manual()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $port = $this->config['proxy-port-manual'];
        if ($port == NULL || !$port)
            return '';

        return $port;
    }

    /**
     * Get proxy port.
     *
     * @return int
     * @throws Engine_Exception
     */

    public function get_proxy_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $port = $this->config['proxy-port'];
        if ($port == NULL || !$port)
            return 0;

        return $port;
    }

    /**
     * Get rule.
     *
     * @param string $id ID
     *
     * @return String
     * @throws Engine_Exception
     */

    public function get_rule($id = 'default')
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $default = $this->config['rule-' . $id];
        if ($default == NULL || !$default)
            return 0;

        return $default;
    }

    /**
     * Get proxy location options.
     *
     * @return array
     * @throws Engine_Exception
     */

    public function get_proxy_location_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        // All LAN interfaces
        $locations = array(
            'self' => lang('wpad_this_server'),
            'custom' => lang('wpad_manually_configure')
        );

        return $locations;
    }

    /**
     * Get proxy port options.
     *
     * @return array
     * @throws Engine_Exception
     */

    public function get_proxy_port_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $locations = array(
            -1 => lang('wpad_manually_configure'),
            0 => lang('wpad_autodetect'),
            3128 => 3128,
            8080=> 8080
        );

        return $locations;
    }

    /**
     * Get rule options.
     *
     * @return array
     * @throws Engine_Exception
     */

    public function get_rule_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $rule = array(
            0 => lang('wpad_via_proxy'),
            1 => lang('wpad_via_direct')
        );

        return $rule;
    }

    /**
     * Get proxy exemption list.
     *
     * @return array
     * @throws Engine_Exception
     */

    public function get_exemption_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $exempt = array();
        if (isset($this->config['exemption-list']))
            $exempt = json_decode($this->config['exemption-list']);
        if ($exempt == NULL || $exempt === FALSE)
            $exempt = array();

        return $exempt;
    }

    /**
     * Get device list.
     *
     * @return array of devices
     * @throws Engine_Exception
     */

    function get_device_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $network_map = new Network_Map();
        $devices = array_merge($network_map->get_mapped_list(), $network_map->get_unknown_list()); 
        return $devices;
    }

    /**
     * Creates a WPAD pac file automatically.
     *
     * @param boolean $force force generation of PAC file
     *
     * @return void
     * @throws Engine_Exception
     */

    function auto_generate_pac($force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        // Do we need to create a PAC file?
        if (!$force && (!$this->get_enabled() || $this->get_custom_pac()))
            return;

        $wpad = array();
        $iptables = array();

        $interfaces = $this->get_interfaces();

        $wpad[] = "function FindProxyForURL(url, host) {";

        $port = -1;
        $default_rule = '';
        if ($this->get_proxy_location() == 'self') {
            // Proxy resides locally on this server
            // Is proxy installed?
            if (clearos_library_installed('web_proxy/Squid')) {
                $squid = new Squid();
                // Is it running?
                if ($squid->get_running_state()) {
                    $port = 3128;
                    // Is Content Filter installed?
                    if (clearos_library_installed('content_filter/DansGuardian.php')) {
                        $dg = new DansGuardian();
                        if ($dg->get_running_state())
                            $port = 8080;
                    }
                }
            }
            // Check for manual override?
            if ($port < 0) {
                // Squid not running running
                $default_rule = "  return \"DIRECT\";";
            } else {
                if ($this->get_proxy_port() < 0)
                    $port = $this->get_proxy_port_manual();

                // Loop through Interfaces, creating default rule and rules for each interface
                foreach ($interfaces as $iface => $ifdata) {
                    // Default rules
                    if ($this->get_rule() == 0)
                        $default_rule .= "PROXY " . $ifdata['ip'] . ":$port; ";
                    else
                        $default_rule = "  return \"DIRECT\";";

                    // Interface specific rules
                    if ($this->get_rule($iface) == 0) {
                        $default_rule .= "PROXY " . $ifdata['ip'] . ":$port; ";
                        $wpad[] = "  if (isInNet(myIpAddress(), \"" . $ifdata['network'] . "\", \"" . $ifdata['netmask'] . "\"))";
                        $wpad[] = "    return \"PROXY " . $ifdata['ip'] . ":$port\";";
                    } else {
                        $wpad[] = "  if (isInNet(myIpAddress(), \"" . $ifdata['network'] . "\", \"" . $ifdata['netmask'] . "\"))";
                        $wpad[] = "    return \"DIRECT\";";
                        $iptables[] = "    $IPTABLES -t nat -I PREROUTING -s " . $ifdata['network'] . "/" . $ifdata['netmask'] . " -j ACCEPT";
                        $iptables[] = "    $IPTABLES -I FORWARD -s " . $ifdata['network'] . "/" . $ifdata['netmask'] . " -j ACCEPT";

                    }
                }
                if ($this->get_rule() == 0)
                    $default_rule = "  return \"" . trim($default_rule) . "\";";
                if (count($interfaces) == 0) {
                    $default_rule = "  return \"DIRECT\";";
                }
            }
        } else {
            // Proxy configured elsewhere
        }
        
        // Exemption by IP
        $exempt_list = $this->get_exemption_list();
        $mapping = $this->get_device_list();
        $wpad[] = "  var myip = myIpAddress();";
        foreach ($exempt_list as $exempt_ip) {
            $wpad[] = "  if (myip == \"" . key($mapping[$exempt_ip]['mapping']) . "\")";
            $wpad[] = "    return \"DIRECT\";";
            $iptables[] = '    $IPTABLES -t nat -I PREROUTING -s " . $exempt_ip . " -j ACCEPT';
            $iptables[] = '    $IPTABLES -I FORWARD -s " . $exempt_ip . " -j ACCEPT';
        }
        $wpad[] = $default_rule;
        $wpad[] = "}";

        $pac = new File(self::FILE_DAT, TRUE);
        if ($pac->exists())
            $pac->move_to(self::FILE_DAT .'.' . date('ymd-hi') . '.bak');
        $pac = new File(self::FILE_DAT, TRUE);
        $pac->create('root', 'root', '0644');
        $pac->add_lines(implode("\n", $wpad));

        $firewall_d = new File(self::FILE_FIREWALL_D, TRUE);
        if ((empty($iptables) && $firewall_d->exists()) || !empty($iptables)) {
            if (empty($iptables) && $firewall_d->exists()) {
                $firewall_d->delete();
            } else {
                if ($firewall_d->exists())
                    $firewall_d->delete();
                $firewall_d->create('root', 'root', '0755');
                $firewall_d->add_lines('# Auto-generated by API - Do not edit');
                $firewall_d->add_lines('if [ "$FW_PROTO" == "ipv4" ]; then true');
                $firewall_d->add_lines(implode("\n", $iptables));
                $firewall_d->add_lines("fi");
            }
            $this->_restart_firewall();
        }
    }

    /**
     * Get LAN interface array.
     *
     * @param boolean $lan_only flag for LAN only type
     *
     * @return array
     * @throws Engine_Exception
     */

    function get_interfaces($lan_only = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        //$iface_options = array (
        //    'filter_virtual' => FALSE,
        //    'filter_vlan' => FALSE
        //);

        $iface_manager = new Iface_Manager();
        $network_interfaces = $iface_manager->get_interface_details($iface_options);
        $ifaces = array();
        foreach ($network_interfaces as $interface => $detail) {
            if (!$detail['configured'] || ($lan_only && $detail['role'] != Role::ROLE_LAN))
                continue;
            $ethnetwork = Network_Utils::get_network_address($detail['address'], $detail['netmask']);
            $ifaces[$interface] = array(
                'ip' => $detail['address'],
                'network' => $ethnetwork,
                'netmask' => $detail['netmask']
            );
        }
        return $ifaces;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
    * loads configuration files.
    *
    * @return void
    * @throws Engine_Exception
    */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG);
            
        $this->config = $configfile->load();

        $this->is_loaded = TRUE;
    }

    /**
     * Generic set routine.
     *
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return  void
     * @throws Engine_Exception
     */

    private function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $regex = str_replace("[", "\\[", $key);
            $regex = str_replace("]", "\\]", $regex);
            $file = new File(self::FILE_CONFIG, TRUE);
            $match = $file->replace_lines("/^$regex\s*=\s*/", "$key = $value\n");
            if (!$match)
                $file->add_lines("$key = $value\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = FALSE;
    }

    /**
     * Restart firewall after change.
     *
     * @return  void
     * @throws Engine_Exception
     */

    private function _restart_firewall()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $firewall = new Firewall(); 
            $firewall->restart();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for wpad status.
     *
     * @param bool $status TRUE/FALSE
     *
     * @return mixed void if enabled is valid, errmsg otherwise
     */

    function validate_enabled($status)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for dns status.
     *
     * @param bool $dns TRUE/FALSE
     *
     * @return mixed void if enabled is valid, errmsg otherwise
     */

    function validate_dns($dns)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for dhcp status.
     *
     * @param bool $dhcp TRUE/FALSE
     *
     * @return mixed void if enabled is valid, errmsg otherwise
     */

    function validate_dhcp($dhcp)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for custom wpad configuration.
     *
     * @param bool $custom TRUE/FALSE
     *
     * @return mixed void if custom is valid, errmsg otherwise
     */

    function validate_custom($custom)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for proxy location.
     *
     * @param mixed $proxy_location location
     *
     * @return mixed void if proxy location is valid, errmsg otherwise
     */

    function validate_proxy_location($proxy_location)
    {
        clearos_profile(__METHOD__, __LINE__);
        if ($proxy_location != 'self' && $proxy_location != 'custom')
            return lang('wpad_proxy_location_is_invalid');
    }

    /**
     * Validation routine for proxy location IP.
     *
     * @param mixed $custom_location location
     *
     * @return mixed void if custom proxy location is valid, errmsg otherwise
     */

    function validate_proxy_ip($custom_location)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!Network_Utils::is_valid_ip($custom_location))
            return lang('wpad_proxy_location_is_invalid');
    }

    /**
     * Validation routine for proxy port.
     *
     * @param int $proxy_port proxy port
     *
     * @return mixed void if proxy port is valid, errmsg otherwise
     */

    function validate_proxy_port($proxy_port)
    {
        clearos_profile(__METHOD__, __LINE__);
        if (!is_numeric($proxy_port) || $proxy_port < -1 || $proxy_port > 65535)
            return lang('wpad_proxy_port_is_invalid');
    }

    /**
     * Validation routine for hostname.
     *
     * @param String $hostname hostname
     *
     * @return mixed void if rule is valid, errmsg otherwise
     */

    function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);
        if (empty($hostname))
            return lang('network_hostname_is_invalid');
    }

    /**
     * Validation routine for rule.
     *
     * @param int $rule rule
     *
     * @return mixed void if rule is valid, errmsg otherwise
     */

    function validate_rule($rule)
    {
        clearos_profile(__METHOD__, __LINE__);
        if (!is_numeric($rule) || $rule < 0 || $rule > 1)
            return lang('wpad_rule_is_invalid');
    }

    /**
     * Validation pac file.
     *
     * @param Sting $pac PAC file
     *
     * @return mixed void if file is valid, errmsg otherwise
     */

    function validate_pac_file($pac)
    {
        clearos_profile(__METHOD__, __LINE__);
        // For another day, would be nice to implement PAC file checker.  Eg:
        // http://code.google.com/p/pacparser/
        if (empty($pac))
            return lang('wpad_pac_end_is_invalid');
    }

    /**
     * Validation exemption list.
     *
     * @param array $exempt list
     *
     * @return mixed void if list is valid, errmsg otherwise
     */

    function validate_exemption_list($exempt)
    {
        clearos_profile(__METHOD__, __LINE__);
        if ($exempt != NULL && !is_array($exempt))
            return lang('wpad_exemption_list_is_invalid');
    }
}
