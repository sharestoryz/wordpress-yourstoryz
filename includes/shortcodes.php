<?php
// Register shortcode for the button
function your_button_shortcode() {
    // Enqueue script for AJAX handling
    wp_enqueue_script('yourstoryz-shortcode-script', plugin_dir_url(__FILE__) . 'js/yourstoryz-shortcode.js', array('jquery'), null, true);

    // Localize script with AJAX URL and nonce for security
    wp_localize_script('yourstoryz-shortcode-script', 'yourstoryz_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('yourstoryz_nonce')
    ));

    // Output the button HTML
    return '<button id="yourbutton" class="button button-primary">Get YourStoryz</button>';
}
add_shortcode('your_button', 'your_button_shortcode');
?>