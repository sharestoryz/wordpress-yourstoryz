<?php

declare(strict_types=1);

namespace YourStoryz;

use YourStoryz\Admin\Menu;
use YourStoryz\Admin\SettingsPage;

final class Plugin
{
    public function boot(): void
    {
        add_action('plugins_loaded', [$this, 'register']);
    }

    public function register(): void
    {
        $settingsPage = new SettingsPage(new Settings);

        $settingsPage->register();
        (new Menu($settingsPage))->register();
    }
}
