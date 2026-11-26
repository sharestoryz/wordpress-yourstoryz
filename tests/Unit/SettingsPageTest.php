<?php

use Brain\Monkey\Functions;
use YourStoryz\Admin\SettingsPage;
use YourStoryz\Api\Gateway;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;
use YourStoryz\Vendor\Saloon\Http\Faking\MockClient;
use YourStoryz\Vendor\Saloon\Http\Faking\MockResponse;
use YourStoryz\Vendor\YourStoryz\PhpSdk\Requests\Users\Me;
use YourStoryz\Vendor\YourStoryz\PhpSdk\YourStoryz;

function yourstoryzSettingsPage(?GatewayProvider $gateways = null): SettingsPage
{
    $settings = new Settings;
    $gateways ??= new GatewayProvider($settings);

    return new SettingsPage(
        $settings,
        new SourceSettings($gateways),
        $gateways,
        pluginFile: '/plugin/YourStoryz.php',
        template: dirname(__DIR__, 2).'/templates/admin/settings.php',
    );
}

function yourstoryzRenderSettings(?GatewayProvider $gateways = null): string
{
    ob_start();
    yourstoryzSettingsPage($gateways)->render();

    return (string) ob_get_clean();
}

function yourstoryzSaveSettings(array $post, ?GatewayProvider $gateways = null): void
{
    $_POST = $post;

    try {
        yourstoryzSettingsPage($gateways)->handleSave();
    } catch (RuntimeException) {
    }
}

beforeEach(function () {
    $this->store = ['yourstoryz_slug' => 'verhalen'];
    $this->flashed = [];

    Functions\when('__')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_html')->alias(fn (string $text): string => htmlspecialchars($text));
    Functions\when('esc_url')->returnArg();
    Functions\when('esc_attr')->alias(fn (string $text): string => htmlspecialchars($text));
    Functions\when('sanitize_title')->alias(fn (string $title): string => strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-')));
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('wp_unslash')->returnArg();
    Functions\when('get_option')->alias(fn (string $key, mixed $default = false): mixed => $this->store[$key] ?? $default);
    Functions\when('update_option')->alias(function (string $key, mixed $value): bool {
        $this->store[$key] = $value;

        return true;
    });
    Functions\when('delete_option')->alias(function (string $key): bool {
        unset($this->store[$key]);

        return true;
    });
    Functions\when('get_page_by_path')->alias(fn (string $path): ?object => $path === 'sample-page' ? (object) ['ID' => 2] : null);
    Functions\when('get_post_types')->justReturn([]);
    Functions\when('get_taxonomies')->justReturn([]);
    Functions\when('current_user_can')->justReturn(true);
    Functions\when('check_admin_referer')->justReturn(1);
    Functions\when('get_current_user_id')->justReturn(1);
    Functions\when('get_transient')->alias(fn (): array|false => $this->flashed ?: false);
    Functions\when('set_transient')->alias(function (string $key, array $notices): bool {
        $this->flashed = $notices;

        return true;
    });
    Functions\when('wp_clear_scheduled_hook')->justReturn(1);
    Functions\when('wp_next_scheduled')->justReturn(false);
    Functions\when('wp_schedule_event')->justReturn(true);
    Functions\when('admin_url')->returnArg();
    Functions\when('add_query_arg')->justReturn('/redirect');
    Functions\when('wp_safe_redirect')->alias(function (): never {
        throw new RuntimeException('redirect');
    });
});

it('shows only the onboarding with store and support links while no token is set', function () {
    yourstoryzStubRender();

    $html = yourstoryzRenderSettings();

    foreach ([
        'https://yourstoryz.com/contact/',
        'https://apps.apple.com/nl/app/yourstoryz/id1544965290',
        'https://play.google.com/store/apps/details?id=com.yourstoryz.storyz&pcampaignid=web_share',
    ] as $url) {
        expect($html)->toContain('href="'.$url.'" target="_blank" rel="noopener noreferrer"');
    }

    expect($html)->toContain('Koppel je YourStoryz-account')
        ->toContain('Nog niet verbonden')
        ->toContain('name="yourstoryz_token"')
        ->toContain('https://site.test/plugin/assets/images/download-on-the-appstore.svg')
        ->not->toContain('name="yourstoryz_slug"')
        ->not->toContain('name="yourstoryz_auto_import"');
});

it('shows the four steps with the source, the slug preview and the account once connected', function () {
    yourstoryzStubRender();
    $this->store += ['yourstoryz_token' => 'secret-token', 'yourstoryz_account' => 'Concept7', 'yourstoryz_source' => ['company_id' => 116, 'department_id' => 7]];
    Functions\when('get_transient')->alias(fn (string $key): array|false => match (true) {
        str_starts_with($key, 'yourstoryz_companies_') => [['id' => 116, 'name' => 'Concept7']],
        str_starts_with($key, 'yourstoryz_departments_116_') => [['id' => 7, 'name' => 'Marketing']],
        default => false,
    });

    $html = yourstoryzRenderSettings();

    expect($html)->toContain('Verbonden als Concept7')
        ->toContain('Concept7 · Marketing')
        ->toContain('<option value="7" selected=\'selected\'>Marketing</option>')
        ->toContain('id="yourstoryz_slug" name="yourstoryz_slug" value="verhalen"')
        ->toContain('>https://site.test/verhalen/</a>')
        ->toContain('form="ys-disconnect-form"')
        ->toContain('<input type="hidden" name="action" value="yourstoryz_disconnect">')
        ->not->toContain('Koppel je YourStoryz-account');
});

it('flashes a success notice for a valid slug and still saves the other fields', function () {
    yourstoryzSaveSettings(['yourstoryz_slug' => 'Nieuws', 'yourstoryz_autoplay' => '1']);

    expect($this->flashed)->toBe([['type' => 'success', 'message' => 'Instellingen opgeslagen.']])
        ->and($this->store['yourstoryz_slug'])->toBe('nieuws')
        ->and($this->store['yourstoryz_autoplay'])->toBe('1');
});

it('flashes an error and keeps the previous slug for an empty slug', function () {
    yourstoryzSaveSettings(['yourstoryz_slug' => '', 'yourstoryz_autoplay' => '1']);

    expect($this->flashed)->toBe([
        ['type' => 'success', 'message' => 'Instellingen opgeslagen.'],
        ['type' => 'error', 'message' => 'Url-slug is leeg of ongeldig; de vorige url-slug is behouden.'],
    ])
        ->and($this->store['yourstoryz_slug'])->toBe('verhalen')
        ->and($this->store['yourstoryz_autoplay'])->toBe('1');
});

it('flashes an error and keeps the previous slug for a reserved slug', function () {
    yourstoryzSaveSettings(['yourstoryz_slug' => 'sample-page', 'yourstoryz_autoplay' => '1']);

    expect($this->flashed)->toBe([
        ['type' => 'success', 'message' => 'Instellingen opgeslagen.'],
        ['type' => 'error', 'message' => 'Deze url-slug is al in gebruik of gereserveerd; de vorige url-slug is behouden.'],
    ])
        ->and($this->store['yourstoryz_slug'])->toBe('verhalen')
        ->and($this->store['yourstoryz_autoplay'])->toBe('1');
});

it('looks up the account once for a site that has a token but no verified account yet', function () {
    yourstoryzStubRender();
    $this->store += ['yourstoryz_token' => 'secret-token'];
    Functions\when('get_transient')->alias(fn (string $key): array|false => str_starts_with($key, 'yourstoryz_companies_') ? [] : false);
    $gateways = new GatewayProvider(new Settings, fn (string $token) => yourstoryzGatewayReturning(['data' => ['name' => 'Concept7']]));

    $html = yourstoryzRenderSettings($gateways);

    expect($html)->toContain('Verbonden als Concept7')
        ->not->toContain('Verbinding niet bevestigd')
        ->and($this->store['yourstoryz_account'])->toBe('Concept7');
});

it('keeps the warning when the account lookup fails', function () {
    yourstoryzStubRender();
    $this->store += ['yourstoryz_token' => 'secret-token'];
    Functions\when('get_transient')->alias(fn (string $key): array|false => str_starts_with($key, 'yourstoryz_companies_') ? [] : false);
    $gateways = new GatewayProvider(new Settings, fn (string $token) => yourstoryzGatewayReturning(['message' => 'Unauthenticated.'], 401));

    $html = yourstoryzRenderSettings($gateways);

    expect($html)->toContain('Verbinding niet bevestigd')
        ->and($this->store)->not->toHaveKey('yourstoryz_account');
});

it('leaves the import and display settings alone when the onboarding posts only a token', function () {
    $this->store += ['yourstoryz_auto_import' => '1', 'yourstoryz_autoplay' => '1'];
    $gateways = new GatewayProvider(new Settings, fn (string $token) => yourstoryzGatewayReturning(['data' => ['name' => 'Concept7']]));

    yourstoryzSaveSettings(['yourstoryz_token' => 'new-token'], $gateways);

    expect($this->store['yourstoryz_token'])->toBe('new-token')
        ->and($this->store['yourstoryz_auto_import'])->toBe('1')
        ->and($this->store['yourstoryz_autoplay'])->toBe('1')
        ->and($this->store['yourstoryz_account'])->toBe('Concept7')
        ->and($this->flashed)->toBe([
            ['type' => 'success', 'message' => 'Instellingen opgeslagen.'],
            ['type' => 'success', 'message' => 'Verbonden als Concept7'],
        ]);
});

it('saves the chosen source together with the other settings', function () {
    $this->store['yourstoryz_token'] = 'secret-token';
    Functions\when('get_transient')->alias(fn (string $key): array|false => match (true) {
        str_starts_with($key, 'yourstoryz_companies_') => [['id' => 116, 'name' => 'Concept7']],
        str_starts_with($key, 'yourstoryz_departments_116_') => [['id' => 7, 'name' => 'Marketing']],
        default => $this->flashed ?: false,
    });

    yourstoryzSaveSettings(['yourstoryz_slug' => 'verhalen', 'yourstoryz_company_id' => '116', 'yourstoryz_department_id' => '7']);

    expect($this->store['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => 7]);
});

it('removes the token and the verified account on disconnect', function () {
    $this->store += ['yourstoryz_token' => 'secret-token', 'yourstoryz_account' => 'Concept7'];

    try {
        yourstoryzSettingsPage()->handleDisconnect();
    } catch (RuntimeException) {
    }

    expect($this->store)->not->toHaveKey('yourstoryz_token')
        ->and($this->store)->not->toHaveKey('yourstoryz_account');
});

function yourstoryzGatewayReturning(array $me, int $status = 200): Gateway
{
    return new Gateway('token', fn (string $token) => (new YourStoryz($token))->withMockClient(new MockClient([
        Me::class => MockResponse::make($me, $status),
    ])));
}

function yourstoryzStubRender(): void
{
    Functions\when('esc_html_e')->echoArg();
    Functions\when('esc_attr_e')->echoArg();
    // An expectation, not a stub: AssetsTest expects plugins_url and Brain Monkey refuses a stub next to one.
    // Brain Monkey keeps the first closure for the whole run, so AssetsTest uses this same closure.
    Functions\expect('plugins_url')->zeroOrMoreTimes()->andReturnUsing(fn (string $path): string => 'https://site.test/plugin/'.$path);
    Functions\when('home_url')->alias(fn (string $path): string => 'https://site.test'.$path);
    Functions\when('wp_nonce_field')->justReturn('');
    Functions\when('selected')->alias(function (mixed $a, mixed $b): void {
        echo (string) $a === (string) $b ? " selected='selected'" : '';
    });
    Functions\when('checked')->justReturn('');
    Functions\when('absint')->alias(fn (mixed $value): int => abs((int) $value));
    Functions\when('wp_date')->justReturn('25 november, 03:00');
}
