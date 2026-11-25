<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

use YourStoryz\Api\ApiException;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Import\ImportRunner;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;

final class SettingsPage
{
    public const ACTION_SAVE = 'yourstoryz_save_settings';

    public const ACTION_TEST = 'yourstoryz_test_connection';

    public const ACTION_SAVE_SOURCE = 'yourstoryz_save_source';

    public const ACTION_REFRESH = 'yourstoryz_refresh_lists';

    private const SITE_URL = 'https://yourstoryz.com';

    private const CONTACT_URL = 'https://yourstoryz.com/contact/';

    private const APP_STORE_URL = 'https://apps.apple.com/nl/app/yourstoryz/id1544965290';

    private const PLAY_STORE_URL = 'https://play.google.com/store/apps/details?id=com.yourstoryz.storyz&pcampaignid=web_share';

    public function __construct(
        private readonly Settings $settings,
        private readonly SourceSettings $source,
        private readonly GatewayProvider $gateways,
        private readonly Notices $notices = new Notices,
        private readonly AdminAction $adminAction = new AdminAction,
        private readonly string $pluginFile = YOURSTORYZ_FILE,
    ) {}

    public function register(): void
    {
        add_action('admin_post_'.self::ACTION_SAVE, [$this, 'handleSave']);
        add_action('admin_post_'.self::ACTION_TEST, [$this, 'handleTest']);
        add_action('admin_post_'.self::ACTION_SAVE_SOURCE, [$this, 'handleSaveSource']);
        add_action('admin_post_'.self::ACTION_REFRESH, [$this, 'handleRefresh']);
    }

    public function handleSave(): void
    {
        $this->adminAction->authorize(self::ACTION_SAVE);

        $input = isset($_POST['yourstoryz_token']) ? sanitize_text_field(wp_unslash($_POST['yourstoryz_token'])) : '';
        $delete = ! empty($_POST['yourstoryz_delete_token']);

        $this->settings->save($input, $delete);

        if (isset($_POST['yourstoryz_import_status'])) {
            $this->settings->saveImportStatus(sanitize_text_field(wp_unslash($_POST['yourstoryz_import_status'])));
        }

        $this->settings->saveAutoImport(! empty($_POST['yourstoryz_auto_import']));

        if (isset($_POST['yourstoryz_import_interval'])) {
            $this->settings->saveImportInterval(sanitize_text_field(wp_unslash($_POST['yourstoryz_import_interval'])));
        }

        $this->settings->saveAutoplay(! empty($_POST['yourstoryz_autoplay']));

        ImportRunner::reschedule();

        $this->notices->flash('success', $delete ? __('Token verwijderd.', 'yourstoryz') : __('Instellingen opgeslagen.', 'yourstoryz'));
        $this->adminAction->redirect(Menu::SLUG);
    }

    public function handleTest(): void
    {
        $this->adminAction->authorize(self::ACTION_TEST);

        if (! $this->settings->hasToken()) {
            $this->notices->flash('error', __('Sla eerst een token op.', 'yourstoryz'));
            $this->adminAction->redirect(Menu::SLUG);
        }

        try {
            $me = $this->gateways->gateway()->me();
            $data = is_array($me['data'] ?? null) ? $me['data'] : $me;
            $name = (string) ($data['name'] ?? $data['email'] ?? '');

            $this->notices->flash('success', $name !== '' ? sprintf(__('Verbonden als %s', 'yourstoryz'), $name) : __('Verbonden.', 'yourstoryz'));
        } catch (ApiException $exception) {
            $this->notices->flash('error', $exception->userMessage());
        }

        $this->adminAction->redirect(Menu::SLUG);
    }

    public function handleSaveSource(): void
    {
        $this->adminAction->authorize(self::ACTION_SAVE_SOURCE);

        $companyId = absint(wp_unslash($_POST['yourstoryz_company_id'] ?? 0));
        $departmentInput = sanitize_text_field(wp_unslash($_POST['yourstoryz_department_id'] ?? ''));
        $departmentId = $departmentInput === '' ? null : absint($departmentInput);

        try {
            $saved = $this->source->save($companyId, $departmentId);

            $this->notices->flash(
                $saved ? 'success' : 'error',
                $saved ? __('Bron opgeslagen.', 'yourstoryz') : __('Ongeldige selectie; de opgeslagen bron is niet gewijzigd.', 'yourstoryz'),
            );
        } catch (ApiException $exception) {
            $this->notices->flash('error', $exception->userMessage());
        }

        $this->adminAction->redirect(Menu::SLUG);
    }

    public function handleRefresh(): void
    {
        $this->adminAction->authorize(self::ACTION_REFRESH);

        $this->source->refresh();
        $this->notices->flash('success', __('Lijsten vernieuwd.', 'yourstoryz'));
        $this->adminAction->redirect(Menu::SLUG);
    }

    public function renderOnboarding(): void
    {
        $badges = [
            [self::APP_STORE_URL, 'download-on-the-appstore.svg', __('Download in de App Store', 'yourstoryz')],
            [self::PLAY_STORE_URL, 'get-it-on-google.png', __('Ontdek het op Google Play', 'yourstoryz')],
        ];

        echo '<div class="card yourstoryz-onboarding"><h2>'.esc_html__('Aan de slag', 'yourstoryz').'</h2><ol>';
        printf(
            '<li>%s <a href="%s" target="_blank" rel="noopener noreferrer">yourstoryz.com</a></li>',
            esc_html__('Maak een YourStoryz-account aan op de website of in de app.', 'yourstoryz'),
            esc_url(self::SITE_URL),
        );
        echo '<li>'.esc_html__('Maak een API-token aan: log in op het dashboard en ga naar My Companies → Tokens → API Tokens. Plak het token hieronder.', 'yourstoryz').'</li>';
        echo '<li>'.esc_html__('Kies de bron en importeer je stories.', 'yourstoryz').'</li></ol><p>';

        foreach ($badges as [$url, $image, $alt]) {
            printf(
                '<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="%s" height="40"></a> ',
                esc_url($url),
                esc_url(plugins_url('assets/images/'.$image, $this->pluginFile)),
                esc_attr($alt),
            );
        }

        echo '</p><p>'.esc_html__('Thumbnails worden opgeslagen in de mediabibliotheek. Video\'s worden niet lokaal opgeslagen en spelen af vanaf YourStoryz.', 'yourstoryz').'</p>';
        printf(
            '<p>%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p></div>',
            esc_html__('Hulp nodig?', 'yourstoryz'),
            esc_url(self::CONTACT_URL),
            esc_html__('Neem contact op met support', 'yourstoryz'),
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $masked = $this->settings->maskedToken();

        echo '<div class="wrap"><h1>'.esc_html__('YourStoryz instellingen', 'yourstoryz').'</h1>';

        $this->renderOnboarding();

        $this->notices->render();

        $this->adminAction->formOpen(self::ACTION_SAVE);
        echo '<table class="form-table" role="presentation"><tr><th scope="row"><label for="yourstoryz_token">'
            .esc_html__('Token', 'yourstoryz').'</label></th><td>';
        printf(
            '<input type="text" class="regular-text" id="yourstoryz_token" name="yourstoryz_token" value="" placeholder="%s" autocomplete="off">',
            esc_attr($masked),
        );
        echo '<p class="description">'.esc_html__('Laat leeg om het huidige token te behouden.', 'yourstoryz').'</p>';

        if ($masked !== '') {
            echo '<p><label><input type="checkbox" name="yourstoryz_delete_token" value="1"> '
                .esc_html__('Token verwijderen', 'yourstoryz').'</label></p>';
        }

        echo '</td></tr>';

        $importStatus = $this->settings->importStatus();
        echo '<tr><th scope="row"><label for="yourstoryz_import_status">'.esc_html__('Status bij import', 'yourstoryz').'</label></th><td>';
        echo '<select id="yourstoryz_import_status" name="yourstoryz_import_status">';
        printf('<option value="draft"%s>%s</option>', selected($importStatus, 'draft', false), esc_html__('Concept', 'yourstoryz'));
        printf('<option value="publish"%s>%s</option>', selected($importStatus, 'publish', false), esc_html__('Gepubliceerd', 'yourstoryz'));
        echo '</select></td></tr>';

        echo '<tr><th scope="row">'.esc_html__('Automatisch importeren', 'yourstoryz').'</th><td>';
        printf(
            '<label for="yourstoryz_auto_import"><input type="checkbox" id="yourstoryz_auto_import" name="yourstoryz_auto_import" value="1"%s> %s</label>',
            checked($this->settings->autoImport(), true, false),
            esc_html__('Stories automatisch importeren', 'yourstoryz'),
        );
        echo '</td></tr>';

        $interval = $this->settings->importInterval();
        echo '<tr><th scope="row"><label for="yourstoryz_import_interval">'.esc_html__('Interval', 'yourstoryz').'</label></th><td>';
        echo '<select id="yourstoryz_import_interval" name="yourstoryz_import_interval">';
        foreach (Settings::IMPORT_INTERVALS as $value) {
            $label = match ($value) {
                'hourly' => __('Elk uur', 'yourstoryz'),
                'twicedaily' => __('Twee keer per dag', 'yourstoryz'),
                'daily' => __('Dagelijks', 'yourstoryz'),
                'weekly' => __('Wekelijks', 'yourstoryz'),
            };

            printf('<option value="%s"%s>%s</option>', esc_attr($value), selected($interval, $value, false), esc_html($label));
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row">'.esc_html__('Autoplay', 'yourstoryz').'</th><td>';
        printf(
            '<label for="yourstoryz_autoplay"><input type="checkbox" id="yourstoryz_autoplay" name="yourstoryz_autoplay" value="1"%s> %s</label>',
            checked($this->settings->autoplay(), true, false),
            esc_html__('Video automatisch afspelen (zonder geluid)', 'yourstoryz'),
        );
        echo '</td></tr></table>';
        submit_button(__('Opslaan', 'yourstoryz'));
        echo '</form>';

        if ($masked !== '') {
            $this->adminAction->formOpen(self::ACTION_TEST);
            submit_button(__('Verbinding testen', 'yourstoryz'), 'secondary');
            echo '</form>';
        }

        (new SourceSection($this->settings, $this->source, $this->adminAction))->render();

        echo '</div>';
    }
}
