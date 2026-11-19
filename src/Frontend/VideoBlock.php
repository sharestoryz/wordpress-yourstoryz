<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use WP_Block;

final class VideoBlock
{
    public function __construct(
        private readonly VideoRenderer $renderer = new VideoRenderer,
        private readonly string $directory = YOURSTORYZ_DIR,
        private readonly ?bool $editing = null,
    ) {}

    public function register(): void
    {
        add_action('init', [$this, 'registerBlock']);
    }

    public function registerBlock(): void
    {
        register_block_type($this->directory.'blocks/video', [
            'render_callback' => [$this, 'render'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function render(array $attributes, string $content, WP_Block $block): string
    {
        $postId = (int) ($block->context['postId'] ?? get_the_ID());

        $video = $postId === 0 ? '' : $this->renderer->renderPost($postId);

        if ($video !== '' || ! $this->isEditing()) {
            return $video;
        }

        return '<div style="display:flex;align-items:center;justify-content:center;aspect-ratio:16/9;width:100%;box-sizing:border-box;border:2px dashed currentColor;background:color-mix(in srgb, currentColor 10%, transparent)">'
            .esc_html(__('Story-video', 'yourstoryz'))
            .'</div>';
    }

    private function isEditing(): bool
    {
        return $this->editing ?? (function_exists('wp_is_serving_rest_request') ? wp_is_serving_rest_request() : (defined('REST_REQUEST') && REST_REQUEST));
    }
}
