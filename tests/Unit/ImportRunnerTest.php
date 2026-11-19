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

    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('get_transient')->alias(fn ($key) => $this->transients[$key] ?? false);
    Functions\when('set_transient')->alias(function ($key, $value) {
        $this->transients[$key] = $value;

        return true;
    });
    Functions\when('delete_transient')->alias(function ($key) {
        if ($key === ImportRunner::LOCK) {
            unset($this->transients[$key]);
        }

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

it('refuses to start while the lock is held and leaves it alone', function () {
    $this->transients[ImportRunner::LOCK] = 123;

    expect($this->runner->run()['status'])->toBe(ImportRunner::STATUS_LOCKED)
        ->and($this->transients)->toHaveKey(ImportRunner::LOCK);
});

it('sets the lock for fifteen minutes during the run and releases it afterwards', function () {
    $ttl = null;
    $lockedDuringRun = false;
    $this->transients['yourstoryz_stories_'.substr(md5('secret'), 0, 8).'_116_0'] = ['stories' => [], 'skipped' => 0];

    Functions\when('set_transient')->alias(function ($key, $value, $expiration = 0) use (&$ttl) {
        if ($key === ImportRunner::LOCK) {
            $ttl = $expiration;
            $this->transients[$key] = $value;
            $this->lockedDuringRun = true;
        }

        return true;
    });

    $outcome = $this->runner->run();

    expect($outcome['status'])->toBe(ImportRunner::STATUS_DONE)
        ->and($outcome['result']->created)->toBe(0)
        ->and($ttl)->toBe(900)
        ->and($this->transients)->not->toHaveKey(ImportRunner::LOCK);
});

it('drops the catalog cache before loading so the import sees fresh data', function () {
    $deleted = [];
    $this->transients['yourstoryz_stories_'.substr(md5('secret'), 0, 8).'_116_0'] = ['stories' => [], 'skipped' => 0];
    Functions\when('delete_transient')->alias(function ($key) use (&$deleted) {
        $deleted[] = $key;

        return true;
    });

    $this->runner->run();

    expect($deleted)->toContain('yourstoryz_stories_'.substr(md5('secret'), 0, 8).'_116_0');
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
