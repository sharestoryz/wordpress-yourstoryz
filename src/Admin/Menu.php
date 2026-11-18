<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

final class Menu
{
    public const SLUG = 'yourstoryz';

    public function __construct(
        private readonly SettingsPage $settingsPage,
        private readonly PreviewPage $previewPage,
    ) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            'YourStoryz',
            'YourStoryz',
            'manage_options',
            self::SLUG,
            [$this->settingsPage, 'render'],
            'dashicons-video-alt3',
        );

        add_submenu_page(
            self::SLUG,
            'Instellingen',
            'Instellingen',
            'manage_options',
            self::SLUG,
            [$this->settingsPage, 'render'],
        );

        add_submenu_page(
            self::SLUG,
            'Voorbeeld',
            'Voorbeeld',
            'manage_options',
            PreviewPage::SLUG,
            [$this->previewPage, 'render'],
        );
    }
}
