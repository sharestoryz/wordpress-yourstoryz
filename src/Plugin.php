<?php

declare(strict_types=1);

namespace YourStoryz;

use YourStoryz\Admin\Menu;
use YourStoryz\Admin\PreviewPage;
use YourStoryz\Admin\SettingsPage;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Import\ImportRunner;

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
        $catalog = new StoryCatalog($gateways);
        $runner = new ImportRunner($settings, $source, $catalog);
        $previewPage = new PreviewPage($source, $catalog, $runner);

        (new PostType)->register();
        $settingsPage->register();
        $previewPage->register();
        add_action(ImportRunner::HOOK, [$runner, 'run']);
        (new Menu($settingsPage, $previewPage))->register();
    }
}
