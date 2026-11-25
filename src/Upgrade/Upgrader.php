<?php

declare(strict_types=1);

namespace YourStoryz\Upgrade;

use YourStoryz\Admin\Menu;
use YourStoryz\Api\ApiException;
use YourStoryz\Import\ImportRunner;
use YourStoryz\Import\LegacyImports;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;

/**
 * One-shot import of the settings of 1.3.2 and 2.0.x. Activation hooks do not fire on a plugin update, so it runs
 * on admin requests while the stored db version is behind.
 */
final class Upgrader
{
    public const OPTION_DB_VERSION = 'yourstoryz_db_version';

    public const OPTION_NOTICE = 'yourstoryz_upgrade_notice';

    public const OPTION_LOCK = 'yourstoryz_upgrade_lock';

    private const LOCK_TTL = 300;

    public const LEGACY_TOKEN_132 = 'yourstoryz_plugin_change_token';

    public const LEGACY_TOKEN_200 = 'yourstoryz_api_token';

    public const LEGACY_TYPE_200 = 'yourstoryz_storiable_type';

    public const LEGACY_ID_200 = 'yourstoryz_storiable_id';

    public const LEGACY_DEPARTMENT_132 = 'yourstoryz_plugin_filter_on_department';

    public const LEGACY_AUTOPLAY_132 = 'yourstoryz_plugin_autoplay';

    public const LEGACY_CRON_132 = 'yourstoryz_enable_cron';

    public const LEGACY_INTERVAL_132 = 'yourstoryz_cron_interval';

    public const LEGACY_PROCESSED_132 = 'yourstoryz_processed_ids';

    /** Post meta that 2.0.x wrote on its posts, holding the story id. */
    public const LEGACY_REFERENCE_META_200 = 'reference_id';

    public const LEGACY_CRON_HOOKS = ['yourstoryz_cron_event', 'yourstoryz_api_fetch'];

    private const LEGACY_OPTIONS_132 = [
        self::LEGACY_TOKEN_132,
        self::LEGACY_DEPARTMENT_132,
        self::LEGACY_AUTOPLAY_132,
        self::LEGACY_CRON_132,
        self::LEGACY_INTERVAL_132,
    ];

    public const LEGACY_OPTIONS = [
        self::LEGACY_TOKEN_132,
        self::LEGACY_TOKEN_200,
        self::LEGACY_TYPE_200,
        self::LEGACY_ID_200,
        self::LEGACY_DEPARTMENT_132,
        self::LEGACY_AUTOPLAY_132,
        self::LEGACY_CRON_132,
        self::LEGACY_INTERVAL_132,
        self::LEGACY_PROCESSED_132,
    ];

    public function __construct(
        private readonly Settings $settings,
        private readonly SourceSettings $source,
        private readonly string $version = YOURSTORYZ_VERSION,
        private readonly LegacyImports $legacyImports = new LegacyImports,
    ) {}

    public function register(): void
    {
        add_action('admin_init', [$this, 'maybeRun']);
        add_action('admin_notices', [$this, 'renderNotice']);
    }

    public function maybeRun(): void
    {
        if (! $this->needsUpgrade()) {
            return;
        }

        if (! add_option(self::OPTION_LOCK, time(), '', false)) {
            if (time() - (int) get_option(self::OPTION_LOCK, 0) < self::LOCK_TTL) {
                return;
            }

            delete_option(self::OPTION_LOCK);

            if (! add_option(self::OPTION_LOCK, time(), '', false)) {
                return;
            }
        }

        try {
            if (get_option(self::OPTION_DB_VERSION, false) === false && $this->anyLegacy(self::LEGACY_OPTIONS)) {
                foreach ($this->legacySteps() as $step) {
                    try {
                        $step();
                    } catch (\Throwable $exception) {
                        add_option(self::OPTION_NOTICE, 'failed', '', false);
                        error_log('YourStoryz upgrade step failed: '.$exception::class.': '.$exception->getMessage());
                    }
                }
            }

            update_option(self::OPTION_DB_VERSION, $this->version);
        } finally {
            delete_option(self::OPTION_LOCK);
        }
    }

    public function renderNotice(): void
    {
        if (get_option(self::OPTION_NOTICE, '') === '' || ! current_user_can('manage_options')) {
            return;
        }

        $notice = get_option(self::OPTION_NOTICE);

        delete_option(self::OPTION_NOTICE);

        $settingsLink = '<a href="'.esc_url(admin_url('admin.php?page='.Menu::SLUG)).'">'.esc_html__('YourStoryz-instellingenpagina', 'yourstoryz').'</a>';

        printf(
            '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
            $notice === 'failed'
                ? sprintf(esc_html__('YourStoryz kon niet alle instellingen van de vorige versie overnemen. Controleer de %s.', 'yourstoryz'), $settingsLink)
                : sprintf(esc_html__('YourStoryz kon je eerdere keuze voor bedrijf of afdeling niet overnemen. Kies een bron op de %s om verder te importeren.', 'yourstoryz'), $settingsLink),
        );
    }

    public function needsUpgrade(): bool
    {
        return version_compare((string) get_option(self::OPTION_DB_VERSION, '0'), $this->version, '<');
    }

    /**
     * @return list<callable(): void>
     */
    private function legacySteps(): array
    {
        return [
            $this->migrateToken(...),
            $this->migrateSource(...),
            $this->migrateImportSchedule(...),
            $this->migrateLegacyImports(...),
        ];
    }

    private function migrateToken(): void
    {
        if ($this->settings->hasToken()) {
            return;
        }

        $this->settings->save($this->legacy(self::LEGACY_TOKEN_200) ?: $this->legacy(self::LEGACY_TOKEN_132));
    }

    private function migrateSource(): void
    {
        if ($this->source->get() !== null) {
            return;
        }

        $selection = $this->selection200() ?? $this->selection132();

        if ($selection === null) {
            return;
        }

        try {
            $companyId = $selection['department'] !== null
                ? $this->companyOfDepartment($selection['department'])
                : ($selection['company'] ?? $this->onlyCompany());
            $saved = $companyId !== null && $this->source->save($companyId, $selection['department']);
        } catch (ApiException) {
            $saved = false;
        }

        if (! $saved) {
            update_option(self::OPTION_NOTICE, 'source', false);
        }
    }

    private function migrateImportSchedule(): void
    {
        if ($this->hasLegacyOption(self::LEGACY_CRON_132)) {
            $this->settings->saveAutoImport($this->legacy(self::LEGACY_CRON_132) === '1');
        } elseif ($this->anyLegacy(self::LEGACY_OPTIONS_132) && $this->legacy(self::LEGACY_TOKEN_200) === '') {
            $this->settings->saveAutoImport(false);
        }

        if ($this->hasLegacyOption(self::LEGACY_INTERVAL_132)) {
            $interval = $this->legacy(self::LEGACY_INTERVAL_132);

            $this->settings->saveImportInterval(array_key_exists($interval, Settings::importIntervals()) ? $interval : Settings::DEFAULT_INTERVAL);
        }

        if ($this->hasLegacyOption(self::LEGACY_AUTOPLAY_132)) {
            $this->settings->saveAutoplay($this->legacy(self::LEGACY_AUTOPLAY_132) === '1');
        }

        foreach (self::LEGACY_CRON_HOOKS as $hook) {
            wp_clear_scheduled_hook($hook);
        }

        ImportRunner::reschedule();
    }

    private function migrateLegacyImports(): void
    {
        $processed = get_option(self::LEGACY_PROCESSED_132, []);
        $processed = is_array($processed) ? $processed : [];

        $this->legacyImports->merge(
            storyIds: $this->referenceIds200(),
            videoIds: array_keys(is_array($processed['videos'] ?? null) ? $processed['videos'] : []),
            contentIds: array_keys(is_array($processed['contents'] ?? null) ? $processed['contents'] : []),
        );
    }

    /**
     * Story ids on 2.0.x posts of any status. 2.0.x inserts without a post type, so they are plain posts; other
     * plugins also use the generic reference_id meta key on their own post types.
     *
     * @return list<string>
     */
    private function referenceIds200(): array
    {
        global $wpdb;

        return array_map('strval', (array) $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT meta.meta_value
            FROM {$wpdb->postmeta} AS meta
            INNER JOIN {$wpdb->posts} AS posts ON posts.ID = meta.post_id
            WHERE meta.meta_key = %s AND posts.post_type = %s",
            self::LEGACY_REFERENCE_META_200,
            'post',
        )));
    }

    /**
     * @return array{company: int|null, department: int|null}|null
     */
    private function selection200(): ?array
    {
        $id = (int) $this->legacy(self::LEGACY_ID_200);

        return match (true) {
            $id <= 0 => null,
            $this->legacy(self::LEGACY_TYPE_200) === 'Company' => ['company' => $id, 'department' => null],
            $this->legacy(self::LEGACY_TYPE_200) === 'Department' => ['company' => null, 'department' => $id],
            default => null,
        };
    }

    /**
     * @return array{company: int|null, department: int|null}|null
     */
    private function selection132(): ?array
    {
        if (! $this->anyLegacy(self::LEGACY_OPTIONS_132)) {
            return null;
        }

        $department = (int) $this->legacy(self::LEGACY_DEPARTMENT_132);

        return ['company' => null, 'department' => $department > 0 ? $department : null];
    }

    /**
     * @throws ApiException
     */
    private function companyOfDepartment(int $departmentId): ?int
    {
        foreach ($this->source->companies() as $company) {
            if (in_array($departmentId, array_column($this->source->departments($company['id']), 'id'), true)) {
                return $company['id'];
            }
        }

        return null;
    }

    private function onlyCompany(): ?int
    {
        try {
            $companies = $this->source->companies();
        } catch (ApiException) {
            return null;
        }

        return count($companies) === 1 ? $companies[0]['id'] : null;
    }

    /**
     * @param  list<string>  $keys
     */
    private function anyLegacy(array $keys): bool
    {
        foreach ($keys as $key) {
            $value = get_option($key, '');

            if (is_array($value) ? $value !== [] : trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function hasLegacyOption(string $key): bool
    {
        return get_option($key, null) !== null;
    }

    private function legacy(string $key): string
    {
        return trim((string) get_option($key, ''));
    }
}
