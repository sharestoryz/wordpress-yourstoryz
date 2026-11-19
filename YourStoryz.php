<?php

use YourStoryz\Plugin;

/**
 * Plugin Name: YourStoryz
 * Description: A plugin to create posts with data from the YourStoryz API.
 * Version: 2.0.0-dev
 * Requires at least: 6.4
 * Requires PHP: 8.3
 * Author: Concept7
 * Author URI: https://concept7.nl/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yourstoryz
 */

// This file must stay parseable on PHP 7.4, so the guard below can run on old versions.

if (! defined('ABSPATH')) {
    exit;
}

define('YOURSTORYZ_VERSION', '2.0.0-dev');
define('YOURSTORYZ_FILE', __FILE__);
define('YOURSTORYZ_DIR', plugin_dir_path(__FILE__));

function yourstoryz_meets_requirements($phpVersion = PHP_VERSION)
{
    return version_compare($phpVersion, '8.3', '>=');
}

function yourstoryz_php_notice()
{
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html__('YourStoryz requires PHP 8.3 or higher and has not been loaded.', 'yourstoryz')
    );
}

function yourstoryz_dependencies_notice()
{
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html__('YourStoryz is missing its dependencies; run composer install.', 'yourstoryz')
    );
}

if (! yourstoryz_meets_requirements()) {
    add_action('admin_notices', 'yourstoryz_php_notice');

    return;
}

spl_autoload_register(function ($class) {
    $prefix = 'YourStoryz\\';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $file = YOURSTORYZ_DIR.'src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

if (! file_exists(YOURSTORYZ_DIR.'vendor-prefixed/autoload.php')) {
    add_action('admin_notices', 'yourstoryz_dependencies_notice');

    return;
}

require_once YOURSTORYZ_DIR.'vendor-prefixed/autoload.php';

register_activation_hook(__FILE__, ['YourStoryz\\PostType', 'activate']);
register_activation_hook(__FILE__, ['YourStoryz\\Import\\ImportRunner', 'schedule']);
register_deactivation_hook(__FILE__, ['YourStoryz\\PostType', 'deactivate']);
register_deactivation_hook(__FILE__, ['YourStoryz\\Import\\ImportRunner', 'unschedule']);

(new Plugin)->boot();
