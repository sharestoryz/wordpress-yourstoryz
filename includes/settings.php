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
    
    add_settings_field(
        'yourstoryz_plugin_filter_on_department',
        'Filter on department',
        'yourstoryz_plugin_filter_on_department_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

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
        'yourstoryz_enable_cron',
        'Enable automatic post updates',
        'yourstoryz_enable_cron_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_field(
        'yourstoryz_cron_interval',
        'Automatically get posts timer',
        'yourstoryz_cron_interval_callback',
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
}

// Connect to api and find available departments
function fetch_departments() {
    $token = get_option('yourstoryz_plugin_change_token');
    $api_url = 'https://dashboard.yourstoryz.com/api/v1/departments';

    $response = wp_remote_get($api_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    return is_array($data) ? $data : [];
}

// Dropdown to select a department from api data
function yourstoryz_plugin_filter_on_department_callback() {
    $departments = fetch_departments();
    $selected_department = get_option('yourstoryz_plugin_filter_on_department');

    $options = '<option value="" ' . selected('', $selected_department) . '>Main company</option>';
    
    foreach ($departments as $department) {
        $options .= '<option value="' . esc_attr($department->id) . '" ' . selected($selected_department, $department->id) . '>'
                    . esc_html($department->name) . 
                    '</option>';
    }
    
    echo <<<HTML
    <select name="yourstoryz_plugin_filter_on_department" id="yourstoryz_plugin_filter_on_department">
        $options
    </select>
    <p class="description">Select the department to filter posts from. Choosing 'Main company' will not apply any filter.</p>
    HTML;
}

// Store autoplay value in options.php (on/off switch)
function yourstoryz_plugin_autoplay_callback() {
    $autoplay = get_option('yourstoryz_plugin_autoplay', '0');

    $checked = checked(1, $autoplay, false);

    echo <<<HTML
    <label for="yourstoryz_plugin_autoplay">
        <input type="checkbox" name="yourstoryz_plugin_autoplay" id="yourstoryz_plugin_autoplay" value="1" $checked />
        Enable autoplay for videos in your blog posts.
    </label>
    HTML;
}

// Store cron job value in options.php (on/off switch)
function yourstoryz_enable_cron_callback() {
    $enable_cron = get_option('yourstoryz_enable_cron', '0');

    $checked = checked(1, $enable_cron, false);

    echo <<<HTML
    <label for="yourstoryz_enable_cron">
        <input type="checkbox" name="yourstoryz_enable_cron" id="yourstoryz_enable_cron" value="1" $checked />
        Enable automatic fetching of posts from the API at your selected interval (default is hourly).
    </label>
    HTML;
}

// Dropdown to set update timer for cron job
function yourstoryz_cron_interval_callback() {
    $cron_interval = get_option('yourstoryz_cron_interval', 'hourly');
    $hourly_selected = selected($cron_interval, 'hourly', false);
    $twicedaily_selected = selected($cron_interval, 'twicedaily', false);
    $daily_selected = selected($cron_interval, 'daily', false);
    $weekly_selected = selected($cron_interval, 'weekly', false);

    echo <<<HTML
    <select name="yourstoryz_cron_interval" id="yourstoryz_cron_interval">
        <option value="hourly" $hourly_selected>Hourly</option>
        <option value="twicedaily" $twicedaily_selected>Twice Daily</option>
        <option value="daily" $daily_selected>Daily</option>
        <option value="weekly" $weekly_selected>Weekly</option>
    </select>
    <p class="description">Set an interval after which to automatically fetch posts from the API.</p>
    HTML;
}


// Input for changing bearer token (api token)
function yourstoryz_plugin_change_token_callback() {
    $token = esc_attr(get_option('yourstoryz_plugin_change_token'));

    echo <<<HTML
    <input type="text" name="yourstoryz_plugin_change_token" id="yourstoryz_plugin_change_token" class="regular-text" value="$token" placeholder="Enter your API token here" />
    <p class="description">Change your API token. An invalid token will remove access to this page.</p>
    HTML;
}
