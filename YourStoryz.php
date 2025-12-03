<?php

/*
Plugin Name: YourStoryz
Description: A plugin to create posts with data from YourStoryz API.
Version: 1.3.2
Author: Concept7
Author URI: https://concept7.nl/
Developer: Nathan Kruit
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// File path constants
define('YOURSTORYZ_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Connector constants
define('YOURSTORYZ_API_URL', 'https://yourstoryz.test');

// Option keys
define('YOURSTORYZ_API_TOKEN_OPTION', 'yourstoryz_api_token');
define('YOURSTORYZ_STORIABLE_TYPE_OPTION', 'yourstoryz_storiable_type');
define('YOURSTORYZ_STORIABLE_ID_OPTION', 'yourstoryz_storiable_id');

// Menu and Cron keys
define('YOURSTORYZ_MENU_SLUG', 'yourstoryz-settings-page');
define('YOURSTORYZ_CRON_HOOK', 'yourstoryz_api_fetch');

require_once YOURSTORYZ_PLUGIN_DIR.'/lib/autoload.php';

require_once YOURSTORYZ_PLUGIN_DIR.'includes/yourstoryz-functions.php';
require_once YOURSTORYZ_PLUGIN_DIR.'includes/class-yourstoryz-admin.php';
require_once YOURSTORYZ_PLUGIN_DIR.'includes/class-yourstoryz-cron.php';

/**
 * Initialize the plugin classes.
 */
function yourstoryz_run()
{
    if (is_admin()) {
        new YourStoryz_Admin;
    }
    // Cron class is needed globally for activation/deactivation hooks and action execution
    new YourStoryz_Cron;
}
add_action('plugins_loaded', 'yourstoryz_run');

/**
 * Schedule cron job on plugin activation.
 */
function yourstoryz_activate()
{
    $cron = new YourStoryz_Cron;
    $cron->schedule_event();
}
register_activation_hook(__FILE__, 'yourstoryz_activate');

/**
 * Clear cron job on plugin deactivation.
 */
function yourstoryz_deactivate()
{
    $cron = new YourStoryz_Cron;
    $cron->clear_event();
}
register_deactivation_hook(__FILE__, 'yourstoryz_deactivate');
