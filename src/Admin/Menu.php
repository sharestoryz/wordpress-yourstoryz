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
        // Priority 9: WordPress adds post type submenus at 10 and the first submenu becomes the top-level link.
        add_action('admin_menu', [$this, 'addMenu'], 9);
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
            __('Instellingen', 'yourstoryz'),
            __('Instellingen', 'yourstoryz'),
            'manage_options',
            self::SLUG,
            [$this->settingsPage, 'render'],
        );

        add_submenu_page(
            self::SLUG,
            __('Voorbeeld', 'yourstoryz'),
            __('Voorbeeld', 'yourstoryz'),
            'manage_options',
            PreviewPage::SLUG,
            [$this->previewPage, 'render'],
        );
    }
}
