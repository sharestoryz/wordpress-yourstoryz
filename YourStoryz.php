<?php
/*
Plugin Name: YourStoryz Plugin
Description: A plugin to create posts with data from YourStoryz API.
Version: 1.0
Author: Concept 7
Author URI: https://concept7.nl/
Developer: Nathan Kruit
*/

// 
include_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
include_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
include_once plugin_dir_path(__FILE__) . 'includes/api-functions.php';
include_once plugin_dir_path(__FILE__) . 'includes/activation.php';
include_once plugin_dir_path(__FILE__) . 'includes/settings.php';

// Add CSS and JS Files
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('yourstoryz-plugin-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('yourstoryz-plugin-script', plugins_url('script.js', __FILE__), array('jquery'), null, true);
});
