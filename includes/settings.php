<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action('admin_init', 'yourstoryz_plugin_register_settings');
// Create settings page and fields
function yourstoryz_plugin_register_settings() {
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_plugin_autoplay');
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_plugin_filter_on_department');
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_plugin_change_token');
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_cron_interval');
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_enable_cron');

    add_settings_section(
        'yourstoryz_plugin_settings_section',
        'Plugin settings',
        '',
        'yourstoryz-plugin-settings'
    );

    add_settings_field(
        'yourstoryz_plugin_autoplay',
        'Autoplay videos',
        'yourstoryz_plugin_autoplay_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_field(
        'yourstoryz_plugin_filter_on_department',
        'Filter on department',
        'yourstoryz_plugin_filter_on_department_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_field(
        'yourstoryz_plugin_change_token',
        'Change API token',
        'yourstoryz_plugin_change_token_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_field(
        'yourstoryz_enable_cron',
        'Enable automatic post updates',
        'yourstoryz_enable_cron_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_field(
        'yourstoryz_cron_interval',
        'Automatic post updates timer',
        'yourstoryz_cron_interval_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );
}
// Store autoplay value in options.php (on/off switch)
function yourstoryz_plugin_autoplay_callback() {
    $autoplay = get_option('yourstoryz_plugin_autoplay');
    echo '<input type="checkbox" name="yourstoryz_plugin_autoplay" value="1" ' . checked(1, $autoplay, false) . ' />';
}
// Dropdown to select a department from api data
function yourstoryz_plugin_filter_on_department_callback() {
    $departments = fetch_departments();
    $selected_department = get_option('yourstoryz_plugin_filter_on_department');

    echo '<select name="yourstoryz_plugin_filter_on_department">
        <option value="" ' . selected('', $selected_department, false) . '>Main company</option>';
    foreach ($departments as $department) {
        $id = esc_attr($department->id);
        $name = esc_html($department->name);
        echo '<option value="' . $id . '" ' . selected($selected_department, $id, false) . '>' . $name . '</option>';
    }
    echo '</select>';
}
// Connect to api and find available departments
function fetch_departments() {
    $token = get_option('yourstoryz_plugin_change_token');
    $api_url = 'https://dashboard.yourstoryz.com/api/v1/departments';

    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ),
    ));

    if (is_wp_error($response)) {
        return array();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    return is_array($data) ? $data : array();
}
// Input for changing bearer token (api token)
function yourstoryz_plugin_change_token_callback() {
    $token = esc_attr(get_option('yourstoryz_plugin_change_token'));
    echo '<input type="text" name="yourstoryz_plugin_change_token" value="' . $token . '" placeholder="' . esc_attr($token) . '" />';
}
// Store cron job value in options.php (on/off switch)
function yourstoryz_enable_cron_callback() {
    $enable_cron = get_option('yourstoryz_enable_cron', '0');
    echo '<input type="checkbox" name="yourstoryz_enable_cron" value="1" ' . checked(1, $enable_cron, false) . ' />';
}
// Dropdown to set update timer for cron job
function yourstoryz_cron_interval_callback() {
    $cron_interval = get_option('yourstoryz_cron_interval', 'hourly');
    echo '<select name="yourstoryz_cron_interval">
        <option value="hourly" ' . selected($cron_interval, 'hourly', false) . '>Hourly</option>
        <option value="twicedaily" ' . selected($cron_interval, 'twicedaily', false) . '>Twice Daily</option>
        <option value="daily" ' . selected($cron_interval, 'daily', false) . '>Daily</option>
        <option value="weekly" ' . selected($cron_interval, 'weekly', false) . '>Weekly</option>
    </select>';
}
?>
