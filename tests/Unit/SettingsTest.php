<?php

use Brain\Monkey\Functions;
use YourStoryz\Settings;

beforeEach(function () {
    $this->store = [];

    Functions\when('__')->returnArg();
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

    foreach (['draft', 'publish'] as $status) {
        $this->store['yourstoryz_import_status'] = $status;
        expect((new Settings)->importStatus())->toBe($status);
    }

    $this->store['yourstoryz_import_status'] = 'pending';
    expect((new Settings)->importStatus())->toBe('draft');

    $this->store['yourstoryz_import_status'] = 'private';
    expect((new Settings)->importStatus())->toBe('draft');
});

it('saves a valid import status without autoload', function () {
    expect((new Settings)->saveImportStatus('publish'))->toBeTrue()
        ->and($this->store['yourstoryz_import_status'])->toBe('publish')
        ->and($this->autoload)->toBeFalse();
});

it('rejects an invalid import status', function () {
    $this->store['yourstoryz_import_status'] = 'draft';

    expect((new Settings)->saveImportStatus('pending'))->toBeFalse()
        ->and($this->store['yourstoryz_import_status'])->toBe('draft');
});

it('defaults to auto import on with a daily interval', function () {
    $settings = new Settings;

    expect($settings->autoImport())->toBeTrue()
        ->and($settings->importInterval())->toBe('daily');
});

it('stores the auto import toggle', function () {
    $settings = new Settings;
    $settings->saveAutoImport(false);

    expect($this->store['yourstoryz_auto_import'])->toBe('0')
        ->and($settings->autoImport())->toBeFalse();

    $settings->saveAutoImport(true);

    expect($settings->autoImport())->toBeTrue();
});

it('defaults autoplay to off and stores the toggle', function () {
    $settings = new Settings;

    expect($settings->autoplay())->toBeFalse();

    $settings->saveAutoplay(true);

    expect($this->store['yourstoryz_autoplay'])->toBe('1')
        ->and($settings->autoplay())->toBeTrue();

    $settings->saveAutoplay(false);

    expect($this->store['yourstoryz_autoplay'])->toBe('0')
        ->and($settings->autoplay())->toBeFalse();
});

it('accepts every whitelisted interval', function (string $interval) {
    $settings = new Settings;

    expect($settings->saveImportInterval($interval))->toBeTrue()
        ->and($settings->importInterval())->toBe($interval);
})->with(['hourly', 'twicedaily', 'daily', 'weekly']);

it('rejects an invalid interval and keeps the stored one', function () {
    $this->store['yourstoryz_import_interval'] = 'hourly';
    $settings = new Settings;

    expect($settings->saveImportInterval('every_minute'))->toBeFalse()
        ->and($settings->importInterval())->toBe('hourly');
});

it('falls back to daily when the stored interval is invalid', function () {
    $this->store['yourstoryz_import_interval'] = 'bogus';

    expect((new Settings)->importInterval())->toBe('daily');
});

function yourstoryzStubSlugEnvironment(array $pageSlugs = [], array $postTypes = [], array $taxonomies = []): void
{
    Functions\when('sanitize_title')->alias(fn (string $title): string => strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-')));
    Functions\when('get_page_by_path')->alias(fn (string $path): ?object => in_array($path, $pageSlugs, true) ? (object) ['ID' => 1] : null);
    Functions\when('get_post_types')->justReturn($postTypes);
    Functions\when('get_taxonomies')->justReturn($taxonomies);
}

it('defaults the slug to stories', function () {
    yourstoryzStubSlugEnvironment();

    expect((new Settings)->slug())->toBe('stories');

    $this->store['yourstoryz_slug'] = '!!!';

    expect((new Settings)->slug())->toBe('stories');
});

it('saves a new slug and flags a rewrite flush', function () {
    yourstoryzStubSlugEnvironment();

    expect((new Settings)->saveSlug('Verhalen'))->toBe(Settings::SLUG_SAVED)
        ->and($this->store['yourstoryz_slug'])->toBe('verhalen')
        ->and($this->store['yourstoryz_flush_rewrite'])->toBe('1')
        ->and((new Settings)->slug())->toBe('verhalen');
});

it('does not flag a flush when the slug is unchanged', function () {
    yourstoryzStubSlugEnvironment();

    expect((new Settings)->saveSlug('stories'))->toBe(Settings::SLUG_UNCHANGED)
        ->and($this->store)->toBe([]);
});

it('keeps the previous slug when the input is empty', function () {
    yourstoryzStubSlugEnvironment();
    $this->store['yourstoryz_slug'] = 'verhalen';

    expect((new Settings)->saveSlug('  !! '))->toBe(Settings::SLUG_INVALID)
        ->and($this->store['yourstoryz_slug'])->toBe('verhalen');
});

it('rejects reserved slugs', function (string $slug) {
    yourstoryzStubSlugEnvironment(
        ['sample-page'],
        [
            (object) ['name' => 'product', 'rewrite' => ['slug' => 'winkel']],
            (object) ['name' => 'yourstoryz_story', 'rewrite' => ['slug' => 'stories']],
            (object) ['name' => 'plain', 'rewrite' => false],
        ],
        [(object) ['name' => 'genre', 'rewrite' => ['slug' => 'soort']]],
    );

    expect((new Settings)->saveSlug($slug))->toBe(Settings::SLUG_RESERVED)
        ->and($this->store)->not->toHaveKey('yourstoryz_slug');
})->with(['wp-admin', 'wp-json', 'category', 'sample-page', 'winkel', 'product', 'soort', 'genre']);

it('consumes the flush flag once', function () {
    $this->store['yourstoryz_flush_rewrite'] = '1';

    expect((new Settings)->consumeFlushRewrite())->toBeTrue()
        ->and($this->store)->not->toHaveKey('yourstoryz_flush_rewrite')
        ->and((new Settings)->consumeFlushRewrite())->toBeFalse();
});
