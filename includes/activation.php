<?php
// Redirect user to settings page of plugin on activation
register_activation_hook(__FILE__, 'yourstoryz_plugin_activate');
add_action('admin_init', 'yourstoryz_plugin_redirect');

function yourstoryz_plugin_activate() {
add_option('yourstoryz_plugin_do_activation_redirect', true);
}

function yourstoryz_plugin_redirect() {
if (get_option('yourstoryz_plugin_do_activation_redirect', false)) {
    delete_option('yourstoryz_plugin_do_activation_redirect');
    if(!isset($_GET['activate-multi']))
    {
        wp_redirect('admin.php?page=yourstoryz-plugin-info');
    }
 }
}
?>