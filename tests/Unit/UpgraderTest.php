<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use YourStoryz\Api\Gateway;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;
use YourStoryz\Upgrade\Upgrader;
use YourStoryz\Vendor\Saloon\Http\Faking\MockClient;
use YourStoryz\Vendor\Saloon\Http\Faking\MockResponse;
use YourStoryz\Vendor\YourStoryz\PhpSdk\Requests\Companies\GetCompanies;
use YourStoryz\Vendor\YourStoryz\PhpSdk\Requests\Companies\GetDepartments;
use YourStoryz\Vendor\YourStoryz\PhpSdk\YourStoryz;

beforeEach(function () {
    $this->store = [];
    $this->cleared = [];
    $this->scheduled = [];
    $this->cap = true;

    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('update_option')->alias(function ($key, $value) {
        $this->store[$key] = $value;

        return true;
    });
    Functions\when('delete_option')->alias(function ($key) {
        unset($this->store[$key]);

        return true;
    });
    Functions\when('get_transient')->justReturn(false);
    Functions\when('set_transient')->justReturn(true);
    Functions\when('wp_clear_scheduled_hook')->alias(function ($hook) {
        $this->cleared[] = $hook;

        return 1;
    });
    Functions\when('wp_next_scheduled')->justReturn(false);
    Functions\when('wp_schedule_event')->alias(function ($time, $interval, $hook) {
        $this->scheduled[] = [$interval, $hook];

        return true;
    });
    Functions\when('admin_url')->alias(fn ($path = '') => 'https://site.test/wp-admin/'.$path);
    Functions\when('current_user_can')->alias(fn () => $this->cap);

    $this->referenceIds = [];
    $test = $this;
    $GLOBALS['wpdb'] = new class($test)
    {
        public string $postmeta = 'wp_postmeta';

        public string $posts = 'wp_posts';

        public function __construct(private $test) {}

        public function prepare(string $query, ...$args): string
        {
            return $query.'|'.implode('|', $args);
        }

        public function get_col(string $query): array
        {
            $this->test->referenceQuery = $query;

            return $this->test->referenceIds;
        }
    };

    $this->mocks = [
        GetCompanies::class => MockResponse::make(['data' => [['id' => 116, 'name' => 'Concept7']]]),
        GetDepartments::class => MockResponse::make(['data' => [['id' => 5, 'name' => 'CES']]]),
    ];
    $this->requests = 0;
    $this->upgrader = function (?array $mocks = null, string $version = '3.0.0') {
        $mocks ??= $this->mocks;
        $settings = new Settings;
        $gateways = new GatewayProvider($settings, fn (string $token) => new Gateway($token, function (string $token) use ($mocks) {
            $this->requests++;

            return (new YourStoryz($token))->withMockClient(new MockClient($mocks));
        }));

        return new Upgrader($settings, new SourceSettings($gateways), $version);
    };
});

afterEach(function () {
    unset($GLOBALS['wpdb']);
});

it('registers on init and admin_notices', function () {
    Actions\expectAdded('init')->once();
    Actions\expectAdded('admin_notices')->once();

    ($this->upgrader)()->register();
});

it('only updates the db version while it is below the plugin version', function (string $stored, string $expected) {
    $this->store = ['yourstoryz_db_version' => $stored];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_db_version'])->toBe($expected);
})->with([
    'older' => ['2.9.0', '3.0.0'],
    'current' => ['3.0.0', '3.0.0'],
    'newer' => ['3.1.0', '3.1.0'],
]);

it('carries legacy options over on the first run only', function () {
    $this->store = ['yourstoryz_api_token' => 'abc'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_token'])->toBe('abc')
        ->and($this->store['yourstoryz_db_version'])->toBe('3.0.0');
});

it('is idempotent', function () {
    $this->store = ['yourstoryz_api_token' => 'abc', 'yourstoryz_storiable_type' => 'Company', 'yourstoryz_storiable_id' => '116'];
    $upgrader = ($this->upgrader)();

    $upgrader->maybeRun();
    $afterFirst = $this->store;
    $scheduled = count($this->scheduled);
    $this->store['yourstoryz_token'] = 'changed';
    $upgrader->maybeRun();

    expect($this->store)->toEqual(['yourstoryz_token' => 'changed'] + $afterFirst)
        ->and($this->scheduled)->toHaveCount($scheduled);
});

it('only sets the db version on a fresh install', function () {
    ($this->upgrader)()->maybeRun();

    expect($this->store)->toBe(['yourstoryz_db_version' => '3.0.0'])
        ->and($this->cleared)->toBe([])
        ->and($this->scheduled)->toBe([])
        ->and($this->requests)->toBe(0);
});

it('takes the token from 1.3.2 and strips Bearer', function () {
    $this->store = ['yourstoryz_plugin_change_token' => 'Bearer secret1234'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_token'])->toBe('secret1234');
});

it('takes the token from 2.0.x', function () {
    $this->store = ['yourstoryz_api_token' => 'new-token'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_token'])->toBe('new-token');
});

it('lets 2.0.x win over 1.3.2', function () {
    $this->store = ['yourstoryz_api_token' => 'new-token', 'yourstoryz_plugin_change_token' => 'old-token'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_token'])->toBe('new-token');
});

it('leaves an existing token alone and keeps the legacy options', function () {
    $this->store = ['yourstoryz_token' => 'mine', 'yourstoryz_api_token' => 'new-token', 'yourstoryz_plugin_change_token' => 'old'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_token'])->toBe('mine')
        ->and($this->store['yourstoryz_api_token'])->toBe('new-token')
        ->and($this->store['yourstoryz_plugin_change_token'])->toBe('old');
});

it('keeps a company as the source without calling the API', function () {
    $this->store = ['yourstoryz_api_token' => 'abc', 'yourstoryz_storiable_type' => 'Company', 'yourstoryz_storiable_id' => '116'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => null])
        ->and($this->requests)->toBe(0);
});

it('resolves the company of a 2.0.x department through the API', function () {
    $this->store = ['yourstoryz_api_token' => 'abc', 'yourstoryz_storiable_type' => 'Department', 'yourstoryz_storiable_id' => '5'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => 5]);
});

it('resolves the company of a 1.3.2 department filter', function () {
    $this->store = ['yourstoryz_plugin_change_token' => 'abc', 'yourstoryz_plugin_filter_on_department' => '5'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => 5]);
});

it('leaves the source empty with a notice when the department is not found', function () {
    $this->store = ['yourstoryz_api_token' => 'abc', 'yourstoryz_storiable_type' => 'Department', 'yourstoryz_storiable_id' => '999'];

    ($this->upgrader)()->maybeRun();

    expect($this->store)->not->toHaveKey('yourstoryz_source')
        ->and($this->store['yourstoryz_upgrade_notice'])->toBe('source')
        ->and($this->store['yourstoryz_db_version'])->toBe('3.0.0')
        ->and($this->store['yourstoryz_token'])->toBe('abc');
});

it('leaves the source empty with a notice and still finishes on an API error', function () {
    $this->store = ['yourstoryz_api_token' => 'abc', 'yourstoryz_storiable_type' => 'Department', 'yourstoryz_storiable_id' => '5'];

    ($this->upgrader)([GetCompanies::class => MockResponse::make([], 500)])->maybeRun();

    expect($this->store)->not->toHaveKey('yourstoryz_source')
        ->and($this->store['yourstoryz_upgrade_notice'])->toBe('source')
        ->and($this->store['yourstoryz_db_version'])->toBe('3.0.0')
        ->and($this->cleared)->toContain('yourstoryz_cron_event', 'yourstoryz_api_fetch');
});

it('uses the only company for 1.3.2 without a department', function () {
    $this->store = ['yourstoryz_plugin_change_token' => 'abc'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => null]);
});

it('leaves the source empty for 1.3.2 when the token sees several companies', function () {
    $this->store = ['yourstoryz_plugin_change_token' => 'abc'];
    $mocks = [GetCompanies::class => MockResponse::make(['data' => [['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]])];

    ($this->upgrader)($mocks)->maybeRun();

    expect($this->store)->not->toHaveKey('yourstoryz_source');
});

it('does not overwrite an existing source', function () {
    $this->store = ['yourstoryz_api_token' => 'abc', 'yourstoryz_storiable_type' => 'Company', 'yourstoryz_storiable_id' => '116', 'yourstoryz_source' => ['company_id' => 7, 'department_id' => null]];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_source'])->toBe(['company_id' => 7, 'department_id' => null]);
});

it('migrates the 1.3.2 autoplay, cron and interval and reschedules', function () {
    $this->store = [
        'yourstoryz_plugin_change_token' => 'abc',
        'yourstoryz_plugin_autoplay' => '1',
        'yourstoryz_enable_cron' => '1',
        'yourstoryz_cron_interval' => 'weekly',
    ];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_autoplay'])->toBe('1')
        ->and($this->store['yourstoryz_auto_import'])->toBe('1')
        ->and($this->store['yourstoryz_import_interval'])->toBe('weekly')
        ->and($this->cleared)->toBe(['yourstoryz_cron_event', 'yourstoryz_api_fetch', 'yourstoryz_import'])
        ->and($this->scheduled)->toBe([['weekly', 'yourstoryz_import']]);
});

it('keeps the new defaults when 1.3.2 never saved the cron settings', function () {
    $this->store = ['yourstoryz_plugin_change_token' => 'abc'];

    ($this->upgrader)()->maybeRun();

    expect($this->store)->not->toHaveKeys(['yourstoryz_auto_import', 'yourstoryz_import_interval', 'yourstoryz_autoplay'])
        ->and($this->scheduled)->toBe([['daily', 'yourstoryz_import']]);
});

it('does not carry legacy options over again on a later version bump', function () {
    $this->store = [
        'yourstoryz_db_version' => '3.0.0',
        'yourstoryz_autoplay' => '0',
        'yourstoryz_plugin_autoplay' => '1',
        'yourstoryz_plugin_change_token' => 'abc',
        'yourstoryz_enable_cron' => '1',
    ];

    ($this->upgrader)(null, '3.0.1')->maybeRun();

    expect($this->store['yourstoryz_autoplay'])->toBe('0')
        ->and($this->store)->not->toHaveKey('yourstoryz_token')
        ->and($this->store['yourstoryz_db_version'])->toBe('3.0.1')
        ->and($this->cleared)->toBe([])
        ->and($this->scheduled)->toBe([]);
});

it('turns auto-import off when 1.3.2 had cron off and schedules nothing', function () {
    $this->store = ['yourstoryz_plugin_change_token' => 'abc', 'yourstoryz_enable_cron' => '0', 'yourstoryz_plugin_autoplay' => '0'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_auto_import'])->toBe('0')
        ->and($this->store['yourstoryz_autoplay'])->toBe('0')
        ->and($this->scheduled)->toBe([]);
});

it('maps an unknown legacy interval to daily', function () {
    $this->store = ['yourstoryz_plugin_change_token' => 'abc', 'yourstoryz_enable_cron' => '1', 'yourstoryz_cron_interval' => 'every_minute'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_import_interval'])->toBe('daily')
        ->and($this->scheduled)->toBe([['daily', 'yourstoryz_import']]);
});

it('clears the old 2.0.x cron and schedules the default', function () {
    $this->store = ['yourstoryz_api_token' => 'abc'];

    ($this->upgrader)()->maybeRun();

    expect($this->cleared)->toContain('yourstoryz_api_fetch', 'yourstoryz_cron_event')
        ->and($this->scheduled)->toBe([['daily', 'yourstoryz_import']]);
});

it('shows the notice once to administrators', function () {
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_url')->returnArg();
    $this->store = ['yourstoryz_upgrade_notice' => 'source'];
    $upgrader = ($this->upgrader)();

    ob_start();
    $upgrader->renderNotice();
    $first = ob_get_clean();
    ob_start();
    $upgrader->renderNotice();
    $second = ob_get_clean();

    expect($first)->toContain('notice-warning')
        ->toContain('<a href="https://site.test/wp-admin/admin.php?page=yourstoryz">YourStoryz-instellingenpagina</a>')
        ->and($second)->toBe('');
});

it('keeps the notice for a user who cannot manage options', function () {
    $this->store = ['yourstoryz_upgrade_notice' => 'source'];
    $this->cap = false;

    ob_start();
    ($this->upgrader)()->renderNotice();

    expect(ob_get_clean())->toBe('')
        ->and($this->store)->toHaveKey('yourstoryz_upgrade_notice');
});

it('fills the legacy registry from 1.3.2 processed ids and 2.0.x reference ids', function () {
    $this->store = [
        'yourstoryz_plugin_change_token' => 'abc',
        'yourstoryz_processed_ids' => [
            'videos' => [11 => ['post_id' => 1], '12' => ['post_id' => 2]],
            'contents' => [21 => ['post_id' => 3]],
        ],
    ];
    $this->referenceIds = ['101', '102', '101'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_legacy_imports'])->toBe(['story_ids' => [101, 102], 'video_ids' => [11, 12], 'content_ids' => [21]])
        ->and($this->referenceQuery)->toContain('wp_posts AS posts')->toContain('post_type = %s')->toContain('|reference_id|post')
        ->and($this->store['yourstoryz_processed_ids'])->toHaveKeys(['videos', 'contents']);
});

it('merges into an existing legacy registry without duplicates', function () {
    $this->store = ['yourstoryz_api_token' => 'abc', 'yourstoryz_legacy_imports' => ['story_ids' => [101], 'video_ids' => [], 'content_ids' => []]];
    $this->referenceIds = ['101', '103'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_legacy_imports']['story_ids'])->toBe([101, 103]);
});

it('ignores a corrupt processed ids option', function () {
    $this->store = ['yourstoryz_plugin_change_token' => 'abc', 'yourstoryz_processed_ids' => 'broken'];

    ($this->upgrader)()->maybeRun();

    expect($this->store['yourstoryz_legacy_imports'])->toBe(['story_ids' => [], 'video_ids' => [], 'content_ids' => []]);
});
