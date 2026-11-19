<?php

use Brain\Monkey\Functions;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Import\ImportRunner;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;
use YourStoryz\StoryCatalog;

beforeEach(function () {
    $this->store = ['yourstoryz_token' => 'secret', 'yourstoryz_source' => ['company_id' => 116, 'department_id' => null]];
    $this->transients = [];
    $this->deletedTransients = [];

    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('add_option')->alias(function ($key, $value) {
        if (array_key_exists($key, $this->store)) {
            return false;
        }

        $this->store[$key] = $value;

        return true;
    });
    Functions\when('update_option')->alias(function ($key, $value) {
        $this->store[$key] = $value;

        return true;
    });
    Functions\when('delete_option')->alias(function ($key) {
        unset($this->store[$key]);

        return true;
    });
    Functions\when('get_transient')->alias(fn ($key) => $this->transients[$key] ?? false);
    Functions\when('set_transient')->alias(function ($key, $value) {
        $this->transients[$key] = $value;

        return true;
    });
    Functions\when('delete_transient')->alias(function ($key) {
        $this->deletedTransients[] = $key;

        return true;
    });
    Functions\when('__')->returnArg(1);

    $GLOBALS['wpdb'] = new class
    {
        public string $posts = 'wp_posts';

        public string $postmeta = 'wp_postmeta';

        public function prepare(string $query, ...$args): string
        {
            return $query;
        }

        public function get_results(string $query): array
        {
            return [];
        }
    };

    $settings = new Settings;
    $gateways = new GatewayProvider($settings);
    $this->runner = new ImportRunner($settings, new SourceSettings($gateways), new StoryCatalog($gateways));
});

afterEach(function () {
    unset($GLOBALS['wpdb']);
});

it('imports nothing without a token', function () {
    unset($this->store['yourstoryz_token']);

    expect($this->runner->run()['status'])->toBe(ImportRunner::STATUS_NO_TOKEN);
});

it('imports nothing without a source', function () {
    unset($this->store['yourstoryz_source']);

    expect($this->runner->run()['status'])->toBe(ImportRunner::STATUS_NO_SOURCE);
});

it('refuses to start while a fresh lock is held and leaves it alone', function () {
    $this->store[ImportRunner::LOCK] = time() - 60;
    $lockedAt = $this->store[ImportRunner::LOCK];

    expect($this->runner->run()['status'])->toBe(ImportRunner::STATUS_LOCKED)
        ->and($this->store[ImportRunner::LOCK])->toBe($lockedAt);
});

it('takes over a stale lock left behind by a dead run', function () {
    $this->store[ImportRunner::LOCK] = time() - ImportRunner::LOCK_TTL - 1;
    $this->transients['yourstoryz_stories_v2_'.substr(md5('secret'), 0, 8).'_116_0'] = [];

    expect($this->runner->run()['status'])->toBe(ImportRunner::STATUS_DONE)
        ->and($this->store)->not->toHaveKey(ImportRunner::LOCK);
});

it('takes the lock without autoload for the run and releases it afterwards', function () {
    $added = [];
    $this->transients['yourstoryz_stories_v2_'.substr(md5('secret'), 0, 8).'_116_0'] = [];
    Functions\when('add_option')->alias(function ($key, $value, $deprecated, $autoload) use (&$added) {
        $added[$key] = $autoload;
        $this->store[$key] = $value;

        return true;
    });

    $outcome = $this->runner->run();

    expect($outcome['status'])->toBe(ImportRunner::STATUS_DONE)
        ->and($outcome['result']->created)->toBe(0)
        ->and($added)->toBe([ImportRunner::LOCK => false])
        ->and($this->store)->not->toHaveKey(ImportRunner::LOCK);
});

it('releases the lock when the import fails', function () {
    $this->transients['yourstoryz_stories_v2_'.substr(md5('secret'), 0, 8).'_116_0'] = ['not a story'];

    $outcome = $this->runner->run();

    expect($outcome['status'])->toBe(ImportRunner::STATUS_ERROR)
        ->and($this->store)->not->toHaveKey(ImportRunner::LOCK);
});

it('drops the catalog cache before loading so the import sees fresh data', function () {
    $this->transients['yourstoryz_stories_v2_'.substr(md5('secret'), 0, 8).'_116_0'] = [];

    $this->runner->run();

    expect($this->deletedTransients)->toContain('yourstoryz_stories_v2_'.substr(md5('secret'), 0, 8).'_116_0');
});

it('schedules the daily event only once', function () {
    $scheduled = [];
    Functions\when('wp_next_scheduled')->alias(fn ($hook) => false);
    Functions\when('wp_schedule_event')->alias(function ($time, $recurrence, $hook) use (&$scheduled) {
        $scheduled[] = [$recurrence, $hook];

        return true;
    });

    ImportRunner::schedule();

    expect($scheduled)->toBe([['daily', 'yourstoryz_import']]);
});

it('does not schedule a second event', function () {
    $scheduled = [];
    Functions\when('wp_next_scheduled')->alias(fn ($hook) => 1234567890);
    Functions\when('wp_schedule_event')->alias(function ($time, $recurrence, $hook) use (&$scheduled) {
        $scheduled[] = $hook;

        return true;
    });

    ImportRunner::schedule();

    expect($scheduled)->toBe([]);
});

it('clears the event on unschedule', function () {
    $cleared = [];
    Functions\when('wp_clear_scheduled_hook')->alias(function ($hook) use (&$cleared) {
        $cleared[] = $hook;

        return 1;
    });

    ImportRunner::unschedule();

    expect($cleared)->toBe(['yourstoryz_import']);
});
