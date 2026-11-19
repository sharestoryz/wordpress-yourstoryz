<?php

declare(strict_types=1);

namespace YourStoryz;

final class Settings
{
    public const OPTION_TOKEN = 'yourstoryz_token';

    public const OPTION_IMPORT_STATUS = 'yourstoryz_import_status';

    public const IMPORT_STATUSES = ['draft', 'pending', 'publish'];

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
}
