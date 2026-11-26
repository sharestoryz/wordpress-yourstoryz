<?php

declare(strict_types=1);

namespace YourStoryz;

final class Settings
{
    public const OPTION_TOKEN = 'yourstoryz_token';

    public const OPTION_IMPORT_STATUS = 'yourstoryz_import_status';

    public const OPTION_AUTO_IMPORT = 'yourstoryz_auto_import';

    public const OPTION_AUTOPLAY = 'yourstoryz_autoplay';

    public const OPTION_IMPORT_INTERVAL = 'yourstoryz_import_interval';

    public const OPTION_SLUG = 'yourstoryz_slug';

    public const OPTION_FLUSH_REWRITE = 'yourstoryz_flush_rewrite';

    public const OPTION_ACCOUNT = 'yourstoryz_account';

    public const DEFAULT_SLUG = 'stories';

    public const SLUG_SAVED = 'saved';

    public const SLUG_UNCHANGED = 'unchanged';

    public const SLUG_INVALID = 'invalid';

    public const SLUG_RESERVED = 'reserved';

    public const IMPORT_STATUSES = ['draft', 'publish'];

    public const DEFAULT_INTERVAL = 'daily';

    public const MASK = '••••';

    public function __construct(
        private readonly ReservedSlugs $reservedSlugs = new ReservedSlugs,
    ) {}

    public function token(): string
    {
        return (string) get_option(self::OPTION_TOKEN, '');
    }

    public function importStatus(): string
    {
        $status = (string) get_option(self::OPTION_IMPORT_STATUS, 'draft');

        return in_array($status, self::IMPORT_STATUSES, true) ? $status : 'draft';
    }

    public function saveImportStatus(string $status): bool
    {
        if (! in_array($status, self::IMPORT_STATUSES, true)) {
            return false;
        }

        update_option(self::OPTION_IMPORT_STATUS, $status, false);

        return true;
    }

    public function autoImport(): bool
    {
        return $this->flag(self::OPTION_AUTO_IMPORT, true);
    }

    public function saveAutoImport(bool $enabled): void
    {
        $this->saveFlag(self::OPTION_AUTO_IMPORT, $enabled);
    }

    public function autoplay(): bool
    {
        return $this->flag(self::OPTION_AUTOPLAY, false);
    }

    public function saveAutoplay(bool $enabled): void
    {
        $this->saveFlag(self::OPTION_AUTOPLAY, $enabled);
    }

    /**
     * @return array<string, string> WP-Cron schedule => label
     */
    public static function importIntervals(): array
    {
        return [
            'hourly' => __('Elk uur', 'yourstoryz'),
            'twicedaily' => __('Twee keer per dag', 'yourstoryz'),
            'daily' => __('Dagelijks', 'yourstoryz'),
            'weekly' => __('Wekelijks', 'yourstoryz'),
        ];
    }

    public function importInterval(): string
    {
        $interval = (string) get_option(self::OPTION_IMPORT_INTERVAL, self::DEFAULT_INTERVAL);

        return array_key_exists($interval, self::importIntervals()) ? $interval : self::DEFAULT_INTERVAL;
    }

    public function saveImportInterval(string $interval): bool
    {
        if (! array_key_exists($interval, self::importIntervals())) {
            return false;
        }

        update_option(self::OPTION_IMPORT_INTERVAL, $interval, false);

        return true;
    }

    public function slug(): string
    {
        $slug = sanitize_title((string) get_option(self::OPTION_SLUG, self::DEFAULT_SLUG));

        return $slug === '' ? self::DEFAULT_SLUG : $slug;
    }

    public function saveSlug(string $input): string
    {
        $slug = sanitize_title($input);

        if ($slug === '') {
            return self::SLUG_INVALID;
        }

        if ($slug === $this->slug()) {
            return self::SLUG_UNCHANGED;
        }

        if ($this->reservedSlugs->contains($slug)) {
            return self::SLUG_RESERVED;
        }

        update_option(self::OPTION_SLUG, $slug, true);
        update_option(self::OPTION_FLUSH_REWRITE, '1', true);

        return self::SLUG_SAVED;
    }

    public function consumeFlushRewrite(): bool
    {
        if ((string) get_option(self::OPTION_FLUSH_REWRITE, '0') !== '1') {
            return false;
        }

        delete_option(self::OPTION_FLUSH_REWRITE);

        return true;
    }

    public function hasToken(): bool
    {
        return $this->token() !== '';
    }

    public function maskedToken(): string
    {
        $token = $this->token();

        if ($token === '') {
            return '';
        }

        return self::MASK.substr($token, -4);
    }

    public function normalize(string $input): string
    {
        return trim((string) preg_replace('/^\s*Bearer\s+/i', '', trim($input)));
    }

    /**
     * Returns whether the stored token changed. A changed token drops the account name verified for the old one.
     */
    public function save(string $input, bool $delete = false): bool
    {
        if ($delete) {
            delete_option(self::OPTION_TOKEN);
            delete_option(self::OPTION_ACCOUNT);

            return true;
        }

        $token = $this->normalize($input);

        if ($token === '' || $token === $this->maskedToken() || $token === $this->token()) {
            return false;
        }

        update_option(self::OPTION_TOKEN, $token, false);
        delete_option(self::OPTION_ACCOUNT);

        return true;
    }

    /**
     * Name of the account the token was last verified against; empty when it has not been verified.
     */
    public function account(): string
    {
        return (string) get_option(self::OPTION_ACCOUNT, '');
    }

    public function saveAccount(string $name): void
    {
        update_option(self::OPTION_ACCOUNT, $name, false);
    }

    private function flag(string $option, bool $default): bool
    {
        return (string) get_option($option, $default ? '1' : '0') === '1';
    }

    private function saveFlag(string $option, bool $enabled): void
    {
        update_option($option, $enabled ? '1' : '0', false);
    }
}
