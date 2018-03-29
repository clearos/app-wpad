<?php

/////////////////////////////////////////////////////////////////////////////
// General information 
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'wpad';
$app['version'] = '2.1.1';
$app['release'] = '2';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('wpad_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('wpad_app_name');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_content_filter_and_proxy');

/////////////////////////////////////////////////////////////////////////////
// Tooltips
/////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-web-server-core',
    'app-network-map-core',
    'app-dhcp-core => 1:1.5.1',
);

$app['core_file_manifest'] = array(
   'wpad.conf' => array(
        'target' => '/etc/clearos/wpad.conf',
        'mode' => '0640',
        'owner' => 'webconfig',
        'group' => 'webconfig',
        'config' => TRUE,
        'config_params' => 'noreplace'
    ),
   'wpad-init' => array(
        'target' => '/usr/sbin/wpad-init',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
    ),
    '10-wpad' => array(
        'target' => '/etc/clearos/firewall.d/10-wpad',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace'
    ),
);

$app['core_directory_manifest'] = array(
   '/var/clearos/wpad' => array('mode' => '755', 'owner' => 'webconfig', 'group' => 'apache')
);

$app['delete_dependency'] = array(
    'app-wpad-core'
);
