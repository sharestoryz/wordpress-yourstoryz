<?php

// Runs when the plugin is deleted from the Plugins screen. Must stay parseable on PHP 7.4.

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('yourstoryz_token');
delete_option('yourstoryz_source');
delete_option('yourstoryz_import_status');
delete_option('yourstoryz_auto_import');
delete_option('yourstoryz_import_interval');
delete_option('yourstoryz_autoplay');
delete_option('yourstoryz_deleted_stories');
delete_option('yourstoryz_import_lock');
delete_option('yourstoryz_db_version');
delete_option('yourstoryz_upgrade_notice');
delete_option('yourstoryz_legacy_imports');

// Options of 1.3.2 and 2.0.x, left in place by the upgrader. UninstallTest keeps this list equal to Upgrader::LEGACY_OPTIONS.
foreach ([
    'yourstoryz_plugin_change_token',
    'yourstoryz_api_token',
    'yourstoryz_storiable_type',
    'yourstoryz_storiable_id',
    'yourstoryz_plugin_filter_on_department',
    'yourstoryz_plugin_autoplay',
    'yourstoryz_enable_cron',
    'yourstoryz_cron_interval',
    'yourstoryz_processed_ids',
] as $legacyOption) {
    delete_option($legacyOption);
}

wp_clear_scheduled_hook('yourstoryz_import');
wp_clear_scheduled_hook('yourstoryz_cron_event');
wp_clear_scheduled_hook('yourstoryz_api_fetch');

global $wpdb;

// Transients live in wp_options; remove both the value and the timeout rows.
foreach (['_transient_', '_transient_timeout_'] as $prefix) {
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like($prefix.'yourstoryz_').'%'
        )
    );
}
