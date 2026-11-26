<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

use YourStoryz\Api\ApiException;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Import\ImportRunner;
use YourStoryz\PostType;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;

final class SettingsPage
{
    public const ACTION_SAVE = 'yourstoryz_save_settings';

    public const ACTION_TEST = 'yourstoryz_test_connection';

    public const ACTION_DISCONNECT = 'yourstoryz_disconnect';

    public const ACTION_REFRESH = 'yourstoryz_refresh_lists';

    public const AJAX_DEPARTMENTS = 'yourstoryz_departments';

    public const HOOK_SUFFIX = 'toplevel_page_'.Menu::SLUG;

    private const ASSET_HANDLE = 'yourstoryz-settings';

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
        private readonly string $template = YOURSTORYZ_DIR.'templates/admin/settings.php',
    ) {}

    public function register(): void
    {
        add_action('admin_post_'.self::ACTION_SAVE, [$this, 'handleSave']);
        add_action('admin_post_'.self::ACTION_TEST, [$this, 'handleTest']);
        add_action('admin_post_'.self::ACTION_DISCONNECT, [$this, 'handleDisconnect']);
        add_action('admin_post_'.self::ACTION_REFRESH, [$this, 'handleRefresh']);
        add_action('wp_ajax_'.self::AJAX_DEPARTMENTS, [$this, 'handleDepartments']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function handleSave(): void
    {
        $this->adminAction->authorize(self::ACTION_SAVE);

        $input = isset($_POST['yourstoryz_token']) ? sanitize_text_field(wp_unslash($_POST['yourstoryz_token'])) : '';
        $tokenChanged = $this->settings->save($input);

        if (isset($_POST['yourstoryz_import_status'])) {
            $this->settings->saveImportStatus(sanitize_text_field(wp_unslash($_POST['yourstoryz_import_status'])));
        }

        $slugResult = Settings::SLUG_UNCHANGED;

        // The onboarding form posts only the token; the other fields, the slug among them, appear once connected.
        // Without this check the missing checkboxes would read as "off".
        if (isset($_POST['yourstoryz_slug'])) {
            $this->settings->saveAutoImport(! empty($_POST['yourstoryz_auto_import']));
            $this->settings->saveAutoplay(! empty($_POST['yourstoryz_autoplay']));
            $slugResult = $this->settings->saveSlug(sanitize_text_field(wp_unslash($_POST['yourstoryz_slug'])));
        }

        if (isset($_POST['yourstoryz_import_interval'])) {
            $this->settings->saveImportInterval(sanitize_text_field(wp_unslash($_POST['yourstoryz_import_interval'])));
        }

        ImportRunner::reschedule();

        $this->notices->flash('success', __('Instellingen opgeslagen.', 'yourstoryz'));

        if (! $tokenChanged && isset($_POST['yourstoryz_company_id']) && $_POST['yourstoryz_company_id'] !== '') {
            $this->saveSource();
        }

        $slugError = match ($slugResult) {
            Settings::SLUG_INVALID => __('Url-slug is leeg of ongeldig; de vorige url-slug is behouden.', 'yourstoryz'),
            Settings::SLUG_RESERVED => __('Deze url-slug is al in gebruik of gereserveerd; de vorige url-slug is behouden.', 'yourstoryz'),
            default => null,
        };

        if ($slugError !== null) {
            $this->notices->flash('error', $slugError);
        }

        if ($tokenChanged) {
            $this->verifyConnection();
        }

        $this->adminAction->redirect(Menu::SLUG);
    }

    public function handleTest(): void
    {
        $this->adminAction->authorize(self::ACTION_TEST);

        if (! $this->settings->hasToken()) {
            $this->notices->flash('error', __('Sla eerst een token op.', 'yourstoryz'));
            $this->adminAction->redirect(Menu::SLUG);
        }

        $this->verifyConnection();
        $this->adminAction->redirect(Menu::SLUG);
    }

    public function handleDisconnect(): void
    {
        $this->adminAction->authorize(self::ACTION_DISCONNECT);

        $this->settings->save('', true);
        $this->notices->flash('success', __('Verbinding verbroken. Het token is verwijderd.', 'yourstoryz'));
        $this->adminAction->redirect(Menu::SLUG);
    }

    public function handleRefresh(): void
    {
        $this->adminAction->authorize(self::ACTION_REFRESH);

        $this->source->refresh();
        $this->notices->flash('success', __('Lijsten vernieuwd.', 'yourstoryz'));
        $this->adminAction->redirect(Menu::SLUG);
    }

    public function handleDepartments(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }

        check_ajax_referer(self::AJAX_DEPARTMENTS);

        try {
            wp_send_json_success($this->source->departments(absint(wp_unslash($_GET['company'] ?? 0))));
        } catch (ApiException $exception) {
            wp_send_json_error(['message' => $exception->userMessage()]);
        }
    }

    public function enqueue(string $hookSuffix): void
    {
        if ($hookSuffix !== self::HOOK_SUFFIX) {
            return;
        }

        wp_enqueue_style(self::ASSET_HANDLE, plugins_url('assets/admin/settings.css', $this->pluginFile), [], YOURSTORYZ_VERSION);
        wp_enqueue_script(self::ASSET_HANDLE, plugins_url('assets/admin/settings.js', $this->pluginFile), [], YOURSTORYZ_VERSION, true);
        wp_localize_script(self::ASSET_HANDLE, 'yourstoryzSettings', [
            'departmentsUrl' => add_query_arg(
                ['action' => self::AJAX_DEPARTMENTS, '_wpnonce' => wp_create_nonce(self::AJAX_DEPARTMENTS)],
                admin_url('admin-ajax.php'),
            ),
            'wholeCompany' => __('Hele company', 'yourstoryz'),
            'confirmDisconnect' => __('Verbinding verbreken? Het token wordt verwijderd en er worden geen nieuwe stories meer geïmporteerd.', 'yourstoryz'),
        ]);
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $view = $this->view();
        $notices = $this->notices;

        require $this->template;
    }

    /**
     * The data the settings template prints, resolved up front so the template makes no API calls.
     *
     * @return array<string, mixed>
     */
    public function view(): array
    {
        $connected = $this->settings->hasToken();
        $slug = $this->settings->slug();
        $intervals = Settings::importIntervals();

        return [
            'connected' => $connected,
            'account' => $connected ? $this->account() : '',
            'masked' => $this->settings->maskedToken(),
            'source' => $connected ? (new SourceSection($this->source))->view() : null,
            'import_status' => $this->settings->importStatus(),
            'auto_import' => $this->settings->autoImport(),
            'interval' => $this->settings->importInterval(),
            'intervals' => $intervals,
            'next_import' => $this->nextImport(),
            'last_import' => $this->lastImport(),
            'autoplay' => $this->settings->autoplay(),
            'slug' => $slug,
            'home_url' => home_url('/'),
            'urls' => [
                'site' => self::SITE_URL,
                'contact' => self::CONTACT_URL,
                'preview' => add_query_arg('page', PreviewPage::SLUG, admin_url('admin.php')),
                'app_store' => self::APP_STORE_URL,
                'play_store' => self::PLAY_STORE_URL,
                'logo' => plugins_url('assets/images/logo.svg', $this->pluginFile),
                'app_store_badge' => plugins_url('assets/images/download-on-the-appstore.svg', $this->pluginFile),
                'play_store_badge' => plugins_url('assets/images/get-it-on-google.png', $this->pluginFile),
            ],
        ];
    }

    private function saveSource(): void
    {
        $companyId = absint(wp_unslash($_POST['yourstoryz_company_id']));
        $departmentInput = sanitize_text_field(wp_unslash($_POST['yourstoryz_department_id'] ?? ''));
        $departmentId = $departmentInput === '' ? null : absint($departmentInput);

        try {
            if (! $this->source->save($companyId, $departmentId)) {
                $this->notices->flash('error', __('Ongeldige bron; de opgeslagen bron is niet gewijzigd.', 'yourstoryz'));
            }
        } catch (ApiException $exception) {
            $this->notices->flash('error', $exception->userMessage());
        }
    }

    /**
     * Sites that had a token before the account was stored get it looked up once, instead of a warning until someone tests.
     */
    private function account(): string
    {
        $account = $this->settings->account();

        if ($account !== '') {
            return $account;
        }

        try {
            $account = $this->accountName();
        } catch (ApiException) {
            return '';
        }

        $this->settings->saveAccount($account);

        return $account;
    }

    /**
     * @throws ApiException
     */
    private function accountName(): string
    {
        $me = $this->gateways->gateway()->me();
        $data = is_array($me['data'] ?? null) ? $me['data'] : $me;

        return (string) ($data['name'] ?? $data['email'] ?? '');
    }

    private function verifyConnection(): void
    {
        try {
            $name = $this->accountName();

            $this->settings->saveAccount($name);
            $this->notices->flash('success', $name !== '' ? sprintf(__('Verbonden als %s', 'yourstoryz'), $name) : __('Verbonden.', 'yourstoryz'));
        } catch (ApiException $exception) {
            $this->notices->flash('error', $exception->userMessage());
        }
    }

    private function nextImport(): ?string
    {
        $timestamp = wp_next_scheduled(ImportRunner::HOOK);

        return $timestamp === false ? null : wp_date('j F, H:i', $timestamp);
    }

    /**
     * @return array{date: string, created: int, total: int}|null
     */
    private function lastImport(): ?array
    {
        $last = get_option(ImportRunner::OPTION_LAST_IMPORT);

        if (! is_array($last) || ! isset($last['time'])) {
            return null;
        }

        $counts = wp_count_posts(PostType::NAME);

        return [
            'date' => wp_date('j F, H:i', (int) $last['time']),
            'created' => (int) ($last['created'] ?? 0),
            'total' => (int) ($counts->publish ?? 0) + (int) ($counts->draft ?? 0),
        ];
    }
}
