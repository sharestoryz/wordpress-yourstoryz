<?php

declare(strict_types=1);

namespace YourStoryz;

use YourStoryz\Admin\Menu;
use YourStoryz\Admin\PreviewPage;
use YourStoryz\Admin\SettingsPage;

final class Plugin
{
    public function boot(): void
    {
        add_action('plugins_loaded', [$this, 'register']);
    }

    public function register(): void
    {
        $settings = new Settings;
        $source = new SourceSettings($settings);
        $settingsPage = new SettingsPage($settings, $source);
        $previewPage = new PreviewPage($source, new StoryCatalog($settings));

        $settingsPage->register();
        $previewPage->register();
        (new Menu($settingsPage, $previewPage))->register();
    }
}
