<?php

declare(strict_types=1);

namespace YourStoryz;

final class Plugin
{
    public function boot(): void
    {
        add_action('plugins_loaded', [$this, 'register']);
    }

    public function register(): void {}
}
