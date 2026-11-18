<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

use Closure;
use YourStoryz\Api\ApiException;
use YourStoryz\Api\Gateway;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;

final class SettingsPage
{
    public const ACTION_SAVE = 'yourstoryz_save_settings';

    public const ACTION_TEST = 'yourstoryz_test_connection';

    public const ACTION_SAVE_SOURCE = 'yourstoryz_save_source';

    public const ACTION_REFRESH = 'yourstoryz_refresh_lists';

    private const NOTICE_TTL = 60;

    /**
     * @param  (Closure(string): Gateway)|null  $gatewayFactory
     */
    public function __construct(
        private readonly Settings $settings,
        private readonly SourceSettings $source,
        private readonly ?Closure $gatewayFactory = null,
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
        $this->authorize(self::ACTION_SAVE);

        $input = isset($_POST['yourstoryz_token']) ? (string) wp_unslash($_POST['yourstoryz_token']) : '';
        $delete = ! empty($_POST['yourstoryz_delete_token']);

        $this->settings->save($input, $delete);
        $this->flash('success', $delete ? 'Token verwijderd.' : 'Instellingen opgeslagen.');
        $this->redirect();
    }

    public function handleTest(): void
    {
        $this->authorize(self::ACTION_TEST);

        if (! $this->settings->hasToken()) {
            $this->flash('error', 'Sla eerst een token op.');
            $this->redirect();
        }

        $token = $this->settings->token();
        $gateway = $this->gatewayFactory ? ($this->gatewayFactory)($token) : new Gateway($token);

        try {
            $me = $gateway->me();
            $data = is_array($me['data'] ?? null) ? $me['data'] : $me;
            $name = (string) ($data['name'] ?? $data['email'] ?? '');

            $this->flash('success', $name !== '' ? 'Verbonden als '.$name : 'Verbonden.');
        } catch (ApiException $exception) {
            $this->flash('error', $exception->userMessage());
        }

        $this->redirect();
    }

    public function handleSaveSource(): void
    {
        $this->authorize(self::ACTION_SAVE_SOURCE);

        $companyId = (int) ($_POST['yourstoryz_company_id'] ?? 0);
        $departmentInput = (string) ($_POST['yourstoryz_department_id'] ?? '');
        $departmentId = $departmentInput === '' ? null : (int) $departmentInput;

        try {
            $saved = $this->source->save($companyId, $departmentId);

            $this->flash(
                $saved ? 'success' : 'error',
                $saved ? 'Bron opgeslagen.' : 'Ongeldige selectie; de opgeslagen bron is niet gewijzigd.',
            );
        } catch (ApiException $exception) {
            $this->flash('error', $exception->userMessage());
        }

        $this->redirect();
    }

    public function handleRefresh(): void
    {
        $this->authorize(self::ACTION_REFRESH);

        $this->source->refresh();
        $this->flash('success', 'Lijsten vernieuwd.');
        $this->redirect();
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $notice = $this->pullNotice();
        $masked = $this->settings->maskedToken();
        $adminPost = admin_url('admin-post.php');

        echo '<div class="wrap"><h1>'.esc_html__('YourStoryz instellingen', 'yourstoryz').'</h1>';

        if ($notice !== null) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message']),
            );
        }

        echo '<form method="post" action="'.esc_url($adminPost).'">';
        echo '<input type="hidden" name="action" value="'.esc_attr(self::ACTION_SAVE).'">';
        wp_nonce_field(self::ACTION_SAVE);
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

        echo '</td></tr></table>';
        submit_button(__('Opslaan', 'yourstoryz'));
        echo '</form>';

        if ($masked !== '') {
            echo '<form method="post" action="'.esc_url($adminPost).'">';
            echo '<input type="hidden" name="action" value="'.esc_attr(self::ACTION_TEST).'">';
            wp_nonce_field(self::ACTION_TEST);
            submit_button(__('Verbinding testen', 'yourstoryz'), 'secondary');
            echo '</form>';
        }

        (new SourceSection($this->settings, $this->source))->render();

        echo '</div>';
    }

    private function authorize(string $action): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'yourstoryz'), '', ['response' => 403]);
        }

        check_admin_referer($action);
    }

    private function flash(string $type, string $message): void
    {
        set_transient($this->noticeKey(), ['type' => $type, 'message' => $message], self::NOTICE_TTL);
    }

    /**
     * @return array{type: string, message: string}|null
     */
    private function pullNotice(): ?array
    {
        $notice = get_transient($this->noticeKey());

        if (! is_array($notice) || ! isset($notice['type'], $notice['message'])) {
            return null;
        }

        delete_transient($this->noticeKey());

        return [
            'type' => $notice['type'] === 'success' ? 'success' : 'error',
            'message' => (string) $notice['message'],
        ];
    }

    private function noticeKey(): string
    {
        return 'yourstoryz_notice_'.get_current_user_id();
    }

    private function redirect(): never
    {
        wp_safe_redirect(add_query_arg('page', Menu::SLUG, admin_url('admin.php')));
        exit;
    }
}
