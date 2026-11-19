<?php

use Brain\Monkey\Functions;
use YourStoryz\Settings;

beforeEach(function () {
    $this->store = [];

    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('update_option')->alias(function ($key, $value, $autoload = null) {
        $this->store[$key] = $value;
        $this->autoload = $autoload;

        return true;
    });
    Functions\when('delete_option')->alias(function ($key) {
        unset($this->store[$key]);

        return true;
    });
});

it('masks the token with the last four characters', function () {
    $this->store['yourstoryz_token'] = 'abcdefgh1234';

    expect((new Settings)->maskedToken())->toBe('••••1234');
});

it('returns an empty mask without a token', function () {
    expect((new Settings)->maskedToken())->toBe('');
});

it('saves the token without autoload', function () {
    (new Settings)->save('secret-token');

    expect($this->store['yourstoryz_token'])->toBe('secret-token')
        ->and($this->autoload)->toBeFalse();
});

it('strips whitespace and the Bearer prefix', function () {
    (new Settings)->save("  Bearer   abc123 \n");

    expect($this->store['yourstoryz_token'])->toBe('abc123');
});

it('keeps the existing token when the input is empty', function () {
    $this->store['yourstoryz_token'] = 'keep-me';

    (new Settings)->save('   ');

    expect($this->store['yourstoryz_token'])->toBe('keep-me');
});

it('keeps the existing token when the masked value is posted back', function () {
    $this->store['yourstoryz_token'] = 'abcdefgh1234';

    (new Settings)->save('••••1234');

    expect($this->store['yourstoryz_token'])->toBe('abcdefgh1234');
});

it('deletes the token when asked to', function () {
    $this->store['yourstoryz_token'] = 'gone';

    (new Settings)->save('ignored', true);

    expect($this->store)->not->toHaveKey('yourstoryz_token');
});

it('defaults the import status to draft and whitelists the values', function () {
    expect((new Settings)->importStatus())->toBe('draft');

    foreach (['draft', 'pending', 'publish'] as $status) {
        $this->store['yourstoryz_import_status'] = $status;
        expect((new Settings)->importStatus())->toBe($status);
    }

    $this->store['yourstoryz_import_status'] = 'private';
    expect((new Settings)->importStatus())->toBe('draft');
});
