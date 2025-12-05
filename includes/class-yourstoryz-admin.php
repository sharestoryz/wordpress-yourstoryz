<?php
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
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
        if ($token) {
            $connector = new YourStoryzConnector(
                $token,
                YOURSTORYZ_API_URL
            );
            try {
                $user = $connector->users()->me()->object();

                echo '<p class="description">User: '.$user->name.' &lt;'.$user->email.'&gt;</p>';
            } catch (UnauthorizedException $exception) {
                echo '<p class="description">'.esc_html($exception->getMessage()).'</p>';
            }
        }
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

        try {
            $response = match ($type) {
                'Company' => $connector->companies()->all(),
                'Department' => $connector->departments()->all(),
            };
        } catch (UnauthorizedException $exception) {
            echo '<p class="description">'.esc_html($exception->getMessage()).'</p>';

            return;
        }

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
            <h1>Welcome to YourStoryz</h1>
            <div class="notice notice-info alt">
                <p><strong>Warning:</strong> This plugin will download videos to your site. We are not responsible for any data usage or storage impacts.</p>
            </div>
            <p>To use this plugin, you need an active subscription to <a href="https://yourstoryz.com/" target="_blank">YourStoryz</a>.</p>
            <h2>How to Get Your API Token?</h2>
            <ol>
                <li>Create an Account:
                    <p>First, create an account on the YourStoryz platform. You can sign up at <a href="https://yourstoryz.com/" target="_blank">yourstoryz.com</a> or via the YourStoryz app available on iOS and Android.</p>
                    <p>
                        <a href="https://play.google.com/store/apps/details?id=com.yourstoryz.storyz&pcampaignid=web_share" target="_blank">
                            <img src="<?php echo esc_url(plugin_dir_url(__FILE__).'../assets/images/get-it-on-google.png'); ?>" alt="Get it on Google Play" style="width: auto; height: 50px;">
                        </a>
                        <a href="https://apps.apple.com/nl/app/yourstoryz/id1544965290" target="_blank">
                            <img src="<?php echo esc_url(plugin_dir_url(__FILE__).'../assets/images/download-on-the-appstore.svg'); ?>" alt="Download on the App Store" style="width: auto; height: 50px;">
                        </a>
                    </p>
                </li>
                <li>Create Your API Token:
                    <p>Once you have an account, you're ready to create your API token. Follow these steps:</p>
                    <ol>
                        <li>Go to the YourStoryz dashboard: <a href="https://dashboard.yourstoryz.com/en/login" target="_blank">dashboard.yourstoryz.com/dashboard</a></li>
                        <li><strong>Navigate to Personal Access Tokens</strong></li>
                        <li>Create your API token.</li>
                        <li>After obtaining your API token, enter it in the provided field within the plugin settings to start using the plugin.</li>
                    </ol>
                </li>
            </ol>
        </div>
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
        <div class="wrap">
            <h2>Need Help?</h2>
            <p>For further assistance, please <a href="https://yourstoryz.com/contact/" target="_blank">contact our support team</a>.</p>
        </div>
        <?php
    }
}
