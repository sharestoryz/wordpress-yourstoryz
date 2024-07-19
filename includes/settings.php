<?php

add_action('admin_init', 'yourstoryz_plugin_register_settings');

function yourstoryz_plugin_register_settings() {
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_plugin_autoplay');

    add_settings_section(
        'yourstoryz_plugin_settings_section',
        'Autoplay Settings',
        'yourstoryz_plugin_settings_section_callback',
        'yourstoryz-plugin-settings'
    );

    add_settings_field(
        'yourstoryz_plugin_autoplay',
        'Autoplay Videos',
        'yourstoryz_plugin_autoplay_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_field(
        'yourstoryz_plugin_change_token',
        'Change API Token',
        'yourstoryz_plugin_change_token_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );
    add_settings_field(
        'yourstoryz_plugin_filter_on_year',
        'Filter on year',
        'yourstoryz_plugin_filter_on_year_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );
    add_settings_field(
        'yourstoryz_plugin_filter_on_date_range',
        'Filter on date',
        'yourstoryz_plugin_filter_on_date_range_callback',
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
}

function yourstoryz_plugin_settings_section_callback() {
    echo 'Configure the settings for YourStoryz plugin.';
}

function yourstoryz_plugin_autoplay_callback() {
    $autoplay = get_option('yourstoryz_plugin_autoplay');
    ?>
    <input type="checkbox" name="yourstoryz_plugin_autoplay" value="1" <?php checked(1, $autoplay, true); ?> />
    <?php
}
function yourstoryz_plugin_change_token_callback() {
    ?>
    <input type="text" name="yourstoryz_plugin_api_token" placeholder="token"  />
    <?php
}
function yourstoryz_plugin_filter_on_year_callback() {
    ?>
    <select name="year">
        <option value="">Choose a year</option>
        <option value="">2024</option>
        <option value="">2023</option>
        <option value="">2022</option>
    </select>
    <?php
}
function yourstoryz_plugin_filter_on_date_range_callback() {
    ?>
    <input type="date" id="start" name="start-date" value="2020-07-22" min="2020-01-01" max="2024-12-31" />
    <input type="date" id="end" name="end-date" value="2024-07-22" min="2020-01-01" max="2048-12-31" />
    <?php
}
function yourstoryz_plugin_filter_on_department_callback() {
    ?>
    <select name="departments">
        <option value="">Choose a department</option>
        <option value="">Dep 1</option>
        <option value="">Dep 2</option>
        <option value="">Dep 3</option>
    </select>
    <?php
}
?>

