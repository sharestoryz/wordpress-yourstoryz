<?php

declare(strict_types=1);

namespace YourStoryz;

use YourStoryz\Admin\Menu;
use YourStoryz\Admin\PreviewPage;
use YourStoryz\Admin\SettingsPage;
use YourStoryz\Api\GatewayProvider;

final class Plugin
{
    public function boot(): void
    {
        add_action('plugins_loaded', [$this, 'register']);
    }

    public function register(): void
    {
        $settings = new Settings;
        $gateways = new GatewayProvider($settings);
        $source = new SourceSettings($gateways);
        $settingsPage = new SettingsPage($settings, $source, $gateways);
        $previewPage = new PreviewPage($source, new StoryCatalog($gateways));

        (new PostType)->register();
        $settingsPage->register();
        $previewPage->register();
        (new Menu($settingsPage, $previewPage))->register();
    }
}
