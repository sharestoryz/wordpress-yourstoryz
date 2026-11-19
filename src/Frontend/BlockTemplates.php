<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use YourStoryz\PostType;

final class BlockTemplates
{
    public function __construct(
        private readonly VideoRenderer $renderer = new VideoRenderer,
        private readonly string $directory = YOURSTORYZ_DIR,
        private readonly ?bool $templatesSupported = null,
    ) {}

    public function register(): void
    {
        add_action('init', [$this, 'registerTemplates']);
        add_filter('the_content', [$this, 'prependVideo'], 9);
    }

    public function registerTemplates(): void
    {
        if (! wp_is_block_theme() || ! $this->supportsTemplates()) {
            return;
        }

        register_block_template('yourstoryz//single-'.PostType::NAME, [
            'title' => __('Enkele story', 'yourstoryz'),
            'description' => __('Toont één story met video, inhoud en tags.', 'yourstoryz'),
            'content' => $this->markup('single-'.PostType::NAME),
        ]);

        register_block_template('yourstoryz//archive-'.PostType::NAME, [
            'title' => __('Story-overzicht', 'yourstoryz'),
            'description' => __('Toont alle stories in een raster.', 'yourstoryz'),
            'content' => $this->markup('archive-'.PostType::NAME),
        ]);
    }

    public function prependVideo(string $content): string
    {
        if ($this->supportsTemplates() || ! wp_is_block_theme()) {
            return $content;
        }

        if (! is_singular(PostType::NAME) || ! in_the_loop() || ! is_main_query()) {
            return $content;
        }

        $postId = (int) get_the_ID();

        return $this->renderer->render(
            (string) get_post_meta($postId, '_ysz_video_url', true),
            (string) get_post_meta($postId, '_ysz_orientation', true),
            (string) get_the_post_thumbnail_url($postId, 'large'),
        ).$content;
    }

    private function supportsTemplates(): bool
    {
        return $this->templatesSupported ?? function_exists('register_block_template');
    }

    private function markup(string $name): string
    {
        return (string) file_get_contents($this->directory.'templates/blocks/'.$name.'.html');
    }
}
