<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use WP_Post;
use YourStoryz\PostType;

final class StoryBlock
{
    /** @var list<int> */
    private static array $rendering = [];

    public function __construct(
        private readonly TemplateLoader $loader = new TemplateLoader,
        private readonly string $directory = YOURSTORYZ_DIR,
        private readonly ?bool $editing = null,
    ) {}

    public function register(): void
    {
        add_action('init', [$this, 'registerBlock']);
    }

    public function registerBlock(): void
    {
        register_block_type($this->directory.'blocks/story', [
            'render_callback' => [$this, 'render'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function render(array $attributes = []): string
    {
        $storyId = (int) ($attributes['storyId'] ?? 0);
        $story = $storyId > 0 ? get_post($storyId) : null;

        if (! $story instanceof WP_Post || $story->post_type !== PostType::NAME || $story->post_status !== 'publish') {
            return $this->isEditing()
                ? $this->wrap('<p>'.esc_html__('Deze story bestaat niet of is niet gepubliceerd.', 'yourstoryz').'</p>')
                : '';
        }

        if (post_password_required($story)) {
            return $this->isEditing()
                ? $this->wrap('<p>'.esc_html__('Deze story is met een wachtwoord beveiligd.', 'yourstoryz').'</p>')
                : '';
        }

        if (in_array($story->ID, self::$rendering, true)) {
            return '';
        }

        self::$rendering[] = $story->ID;

        try {
            wp_enqueue_style(Assets::HANDLE);

            return $this->wrap((new StoryPage($this->loader, $this->directory))->article($story, 2));
        } finally {
            array_pop(self::$rendering);
        }
    }

    private function wrap(string $html): string
    {
        return sprintf('<div %s>%s</div>', get_block_wrapper_attributes(), $html);
    }

    private function isEditing(): bool
    {
        return $this->editing ?? EditorContext::isBlockRenderer();
    }
}
