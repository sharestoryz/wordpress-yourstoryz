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

    public const IMPORT_STATUSES = ['draft', 'publish'];

    public const IMPORT_INTERVALS = ['hourly', 'twicedaily', 'daily', 'weekly'];

    public const DEFAULT_INTERVAL = 'daily';

    public const MASK = '••••';

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

    public function importInterval(): string
    {
        $interval = (string) get_option(self::OPTION_IMPORT_INTERVAL, self::DEFAULT_INTERVAL);

        return in_array($interval, self::IMPORT_INTERVALS, true) ? $interval : self::DEFAULT_INTERVAL;
    }

    public function saveImportInterval(string $interval): bool
    {
        if (! in_array($interval, self::IMPORT_INTERVALS, true)) {
            return false;
        }

        update_option(self::OPTION_IMPORT_INTERVAL, $interval, false);

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

    public function save(string $input, bool $delete = false): void
    {
        if ($delete) {
            delete_option(self::OPTION_TOKEN);

            return;
        }

        $token = $this->normalize($input);

        if ($token === '' || $token === $this->maskedToken()) {
            return;
        }

        update_option(self::OPTION_TOKEN, $token, false);
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
