<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use YourStoryz\PostType;

final class Assets
{
    public const HANDLE = 'yourstoryz-frontend';

    private const STYLESHEET = 'assets/build/frontend.css';

    public function __construct(
        private readonly string $directory = YOURSTORYZ_DIR,
        private readonly string $file = YOURSTORYZ_FILE,
        private readonly string $version = YOURSTORYZ_VERSION,
    ) {}

    public function register(): void
    {
        add_action('init', [$this, 'registerStyle']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function registerStyle(): void
    {
        if (! file_exists($this->directory.self::STYLESHEET)) {
            return;
        }

        wp_register_style(
            self::HANDLE,
            plugins_url(self::STYLESHEET, $this->file),
            [],
            $this->version,
        );
    }

    public function enqueue(): void
    {
        if (! is_singular(PostType::NAME) && ! is_post_type_archive(PostType::NAME)) {
            return;
        }

        if (! file_exists($this->directory.self::STYLESHEET)) {
            return;
        }

        wp_enqueue_style(
            self::HANDLE,
            plugins_url(self::STYLESHEET, $this->file),
            [],
            $this->version,
        );
    }
}
