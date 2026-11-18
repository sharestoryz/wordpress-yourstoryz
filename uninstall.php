<?php

// Runs when the plugin is deleted from the Plugins screen. Must stay parseable on PHP 7.4.

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('yourstoryz_token');
delete_option('yourstoryz_source');

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
