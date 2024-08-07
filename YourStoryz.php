<?php
/*
Plugin Name: YourStoryz Plugin
Description: A plugin to create posts with data from YourStoryz API.
Version: 1.0
Author: Concept 7
Author URI: https://concept7.nl/
Developer: Nathan Kruit
*/

include_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
include_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
include_once plugin_dir_path(__FILE__) . 'includes/cron-job.php';
include_once plugin_dir_path(__FILE__) . 'includes/api-functions.php';
include_once plugin_dir_path(__FILE__) . 'includes/settings.php';