<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use WP_Post;
use WP_Term;
use YourStoryz\PostType;
use YourStoryz\StoryMeta;

final class StoryBlock
{
    /** @var list<int> */
    private static array $rendering = [];

    public function __construct(
        private readonly TemplateLoader $loader = new TemplateLoader,
        private readonly VideoRenderer $video = new VideoRenderer,
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

        if (in_array($story->ID, self::$rendering, true)) {
            return '';
        }

        self::$rendering[] = $story->ID;

        try {
            return $this->wrap($this->article($story));
        } finally {
            array_pop(self::$rendering);
        }
    }

    private function article(WP_Post $story): string
    {
        $tags = array_map(
            fn (WP_Term $tag): array => ['name' => $tag->name, 'url' => (string) get_tag_link($tag)],
            get_the_tags($story->ID) ?: [],
        );

        $outerPost = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $story;
        setup_postdata($story);

        try {
            $content = apply_filters('the_content', $story->post_content);
        } finally {
            $GLOBALS['post'] = $outerPost;

            if ($outerPost instanceof WP_Post) {
                setup_postdata($outerPost);
            }
        }

        ob_start();
        echo '<article class="ys:flex ys:flex-col ys:gap-6">';
        echo '<h2 class="ys:m-0">'.esc_html(get_the_title($story)).'</h2>';
        echo $this->video->renderPost($story->ID);
        $this->loader->part('author-line', ['author' => (string) get_post_meta($story->ID, StoryMeta::AUTHOR, true)]);
        echo '<div class="ys:w-full">'.$content.'</div>';
        $this->loader->part('tag-list', ['tags' => $tags]);
        echo '</article>';

        return (string) ob_get_clean();
    }

    private function wrap(string $html): string
    {
        return sprintf('<div %s>%s</div>', get_block_wrapper_attributes(), $html);
    }

    private function isEditing(): bool
    {
        return $this->editing ?? (function_exists('wp_is_serving_rest_request') ? wp_is_serving_rest_request() : (defined('REST_REQUEST') && REST_REQUEST));
    }
}
