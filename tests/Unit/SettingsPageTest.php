<?php

use Brain\Monkey\Functions;
use YourStoryz\Admin\SettingsPage;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;

it('renders the onboarding box with support and store links', function () {
    Functions\when('__')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\expect('plugins_url')->twice()->andReturnUsing(fn (string $path, string $file): string => 'https://site.test/plugin/'.$path);

    $page = new SettingsPage(
        new Settings,
        (new ReflectionClass(SourceSettings::class))->newInstanceWithoutConstructor(),
        (new ReflectionClass(GatewayProvider::class))->newInstanceWithoutConstructor(),
        pluginFile: '/plugin/YourStoryz.php',
    );

    ob_start();
    $page->renderOnboarding();
    $html = ob_get_clean();

    foreach ([
        'https://yourstoryz.com/contact/',
        'https://apps.apple.com/nl/app/yourstoryz/id1544965290',
        'https://play.google.com/store/apps/details?id=com.yourstoryz.storyz&pcampaignid=web_share',
    ] as $url) {
        expect($html)->toContain('<a href="'.$url.'" target="_blank" rel="noopener noreferrer">');
    }

    expect($html)->toContain('class="card yourstoryz-onboarding"')
        ->and($html)->toContain('https://site.test/plugin/assets/images/download-on-the-appstore.svg')
        ->and($html)->toContain('https://site.test/plugin/assets/images/get-it-on-google.png');
});

function yourstoryzSettingsPage(): SettingsPage
{
    return new SettingsPage(
        new Settings,
        (new ReflectionClass(SourceSettings::class))->newInstanceWithoutConstructor(),
        (new ReflectionClass(GatewayProvider::class))->newInstanceWithoutConstructor(),
        pluginFile: '/plugin/YourStoryz.php',
    );
}

function yourstoryzSaveSettings(array $post): void
{
    $_POST = $post;

    try {
        yourstoryzSettingsPage()->handleSave();
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

it('renders the slug row with the escaped current value and an example url', function () {
    $this->store['yourstoryz_slug'] = 'verhalen';
    Functions\expect('plugins_url')->twice()->andReturnUsing(fn (string $path, string $file): string => 'https://site.test/plugin/'.$path);
    Functions\when('home_url')->alias(fn (string $path): string => 'https://site.test'.$path);
    Functions\when('wp_nonce_field')->justReturn('');
    Functions\when('submit_button')->justReturn('');
    Functions\when('selected')->justReturn('');
    Functions\when('checked')->justReturn('');

    ob_start();
    yourstoryzSettingsPage()->render();
    $html = ob_get_clean();

    expect($html)->toContain('<label for="yourstoryz_slug">Url-slug</label>')
        ->toContain('id="yourstoryz_slug" name="yourstoryz_slug" value="verhalen"')
        ->toContain('Stories staan op https://site.test/verhalen/');
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
