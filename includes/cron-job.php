<?php
function yourstoryz_manage_cron_event() {
    $cron_interval = get_option('yourstoryz_cron_interval', 'hourly'); 
    $enable_cron = get_option('yourstoryz_enable_cron', '0'); 

    if ($enable_cron === '1') {
        if (!wp_next_scheduled('yourstoryz_cron_event')) {
            wp_schedule_event(time(), $cron_interval, 'yourstoryz_cron_event');
        }
    } else {
        $timestamp = wp_next_scheduled('yourstoryz_cron_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'yourstoryz_cron_event');
        }
    }
}
add_action('wp', 'yourstoryz_manage_cron_event');

add_action('yourstoryz_cron_event', 'call_api');

function yourstoryz_plugin_activation() {
    yourstoryz_manage_cron_event();
}
register_activation_hook(__FILE__, 'yourstoryz_plugin_activation');

function yourstoryz_plugin_deactivation() {
    $timestamp = wp_next_scheduled('yourstoryz_cron_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'yourstoryz_cron_event');
    }
}
register_deactivation_hook(__FILE__, 'yourstoryz_plugin_deactivation');

function yourstoryz_update_cron_schedule() {
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'yourstoryz_plugin_settings_group') {
        yourstoryz_manage_cron_event();
    }
}
add_action('admin_init', 'yourstoryz_update_cron_schedule');
?>
