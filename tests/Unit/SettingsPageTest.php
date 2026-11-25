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
