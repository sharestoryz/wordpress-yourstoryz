<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use YourStoryz\PostType;

final class Assets
{
    public const HANDLE = 'yourstoryz-frontend';

    public const VIDEO_HANDLE = 'yourstoryz-video';

    private const STYLESHEET = 'assets/build/frontend.css';

    private const VIDEO_SCRIPT = 'assets/js/video.js';

    public function __construct(
        private readonly string $directory = YOURSTORYZ_DIR,
        private readonly string $file = YOURSTORYZ_FILE,
        private readonly string $version = YOURSTORYZ_VERSION,
    ) {}

    public function register(): void
    {
        add_action('init', [$this, 'registerAssets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function registerAssets(): void
    {
        wp_register_script(
            self::VIDEO_HANDLE,
            plugins_url(self::VIDEO_SCRIPT, $this->file),
            [],
            $this->version,
            ['in_footer' => true, 'strategy' => 'defer'],
        );

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

        wp_enqueue_style(self::HANDLE);
    }
}
