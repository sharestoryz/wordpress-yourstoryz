<?php
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

use YourStoryz\PhpSdk\YourStoryz as YourStoryzConnector;

class YourStoryz_Admin
{
    public function __construct()
    {
        // Add all hooks within the constructor
        add_action('admin_menu', [$this, 'add_settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Creates the top-level menu page.
     */
    public function add_settings_menu()
    {
        add_menu_page(
            'YourStoryz Settings',
            'YourStoryz',
            'manage_options',
            YOURSTORYZ_MENU_SLUG,
            [$this, 'settings_page_content'],
            'dashicons-format-video',
            90
        );
    }

    /**
     * Registers all settings and sections.
     */
    public function register_settings()
    {
        register_setting('yourstoryz_settings_group', YOURSTORYZ_API_TOKEN_OPTION, ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '']);
        register_setting('yourstoryz_settings_group', YOURSTORYZ_STORIABLE_TYPE_OPTION, ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'Company']);
        register_setting('yourstoryz_settings_group', YOURSTORYZ_STORIABLE_ID_OPTION, ['type' => 'int', 'sanitize_callback' => 'absint', 'default' => 0]);

        add_settings_section('yourstoryz_api_section', '🔐 API Token Configuration', [$this, 'api_section_cb'], YOURSTORYZ_MENU_SLUG);
        add_settings_field(YOURSTORYZ_API_TOKEN_OPTION, 'YourStoryz API Token', [$this, 'api_field_cb'], YOURSTORYZ_MENU_SLUG, 'yourstoryz_api_section');

        add_settings_section('yourstoryz_storiable_section', '📊 Storiable Data Settings', [$this, 'storiable_section_cb'], YOURSTORYZ_MENU_SLUG);
        add_settings_field(YOURSTORYZ_STORIABLE_TYPE_OPTION, 'Storiable Type Option', [$this, 'storiable_type_field_cb'], YOURSTORYZ_MENU_SLUG, 'yourstoryz_storiable_section');
        add_settings_field(YOURSTORYZ_STORIABLE_ID_OPTION, 'Storiable ID', [$this, 'storiable_id_field_cb'], YOURSTORYZ_MENU_SLUG, 'yourstoryz_storiable_section');
    }

    public function api_section_cb()
    {
        echo '<p>Enter your secret API token to establish a connection with the YourStoryz service.</p>';
    }

    public function storiable_section_cb()
    {
        echo '<p>Define how the content should be grouped and which specific ID should be fetched.</p>';
    }

    public function api_field_cb()
    {
        $token = get_option(YOURSTORYZ_API_TOKEN_OPTION);
        ?>
        <input type="text" name="<?php echo YOURSTORYZ_API_TOKEN_OPTION; ?>" value="<?php echo esc_attr($token); ?>" class="regular-text" placeholder="Paste your YourStoryz API token here"/>
        <?php
    }

    public function storiable_type_field_cb()
    {
        $type = get_yourstoryz_storiable_type();
        $options = ['Company', 'Department'];
        foreach ($options as $option) {
            ?>
            <label style="margin-right: 15px;">
                <input type="radio" name="<?php echo YOURSTORYZ_STORIABLE_TYPE_OPTION; ?>" value="<?php echo esc_attr($option); ?>" <?php checked($type, $option); ?> />
                <?php echo esc_html($option); ?>
            </label>
            <?php
        }
        echo '<p class="description">Select the primary grouping method (Storiable Type).</p>';
    }

    public function storiable_id_field_cb()
    {
        $type = get_yourstoryz_storiable_type();
        $id = get_yourstoryz_storiable_id();

        $connector = new YourStoryzConnector(
            get_yourstoryz_api_token(),
            YOURSTORYZ_API_URL
        );

        $response = match ($type) {
            'Company' => $connector->companies()->all(),
            'Department' => $connector->departments()->all(),
        };

        $options = array_map(fn ($storiable) => [
            'value' => $storiable['id'],
            'label' => $storiable['name'],
        ], $response->json());

        ?>
        <select name="<?php echo YOURSTORYZ_STORIABLE_ID_OPTION; ?>">
            <option value="">--------</option>
            <?php foreach ($options as $option) { ?>
                <option value="<?php echo esc_attr($option['value']); ?>" <?php selected($id, $option['value']); ?>>
                    <?php echo esc_html($option['label']); ?>
                </option>
            <?php } ?>
        </select>
        <p class="description">Select the specific ID to fetch data for.</p>
        <?php
    }

    /**
     * Displays the full settings page content.
     */
    public function settings_page_content()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('yourstoryz_settings_group');
        do_settings_sections(YOURSTORYZ_MENU_SLUG);
        submit_button('Save YourStoryz Settings');
        ?>
            </form>
        </div>
        <?php
    }
}
