<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use WP_Post;
use WP_Query;
use YourStoryz\PostType;

/**
 * Renders the single story page and the story overview. Classic themes call it from the PHP templates,
 * block themes through the yourstoryz/story-page block in the plugin's block templates, so both get the same markup.
 */
final class StoryPage
{
    private const RELATED_COUNT = 3;

    private const EXCERPT_WORDS = 28;

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
        register_block_type($this->directory.'blocks/story-page', [
            'render_callback' => [$this, 'render'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function render(array $attributes = []): string
    {
        $archive = ($attributes['view'] ?? 'single') === 'archive';

        if ($this->isEditing()) {
            return '<div style="padding:2rem;border:2px dashed currentColor;text-align:center">'
                .esc_html($archive ? __('Story-overzicht', 'yourstoryz') : __('Story', 'yourstoryz'))
                .'</div>';
        }

        return $archive ? $this->archive($GLOBALS['wp_query']) : $this->single((int) get_queried_object_id());
    }

    public function single(int $postId): string
    {
        $story = get_post($postId);

        if (! $story instanceof WP_Post || $story->post_type !== PostType::NAME) {
            return '';
        }

        $archiveUrl = (string) get_post_type_archive_link(PostType::NAME);

        ob_start();
        echo $this->article($story, 1, $archiveUrl);
        $this->loader->part('story-related', [
            'stories' => array_map(StoryCard::fromPost(...), $this->related($story->ID)),
            'archive_url' => $archiveUrl,
        ]);

        return $this->wrap((string) ob_get_clean());
    }

    public function archive(WP_Query $query): string
    {
        /** @var list<WP_Post> $posts */
        $posts = array_values(array_filter($query->posts, fn (mixed $post): bool => $post instanceof WP_Post));
        $firstPage = max(1, (int) get_query_var('paged')) === 1;
        $featured = $firstPage && $posts !== [] ? array_shift($posts) : null;
        $total = (int) $query->found_posts;

        ob_start();
        $this->loader->part('story-archive', [
            'title' => post_type_archive_title('', false) ?: __('Stories', 'yourstoryz'),
            'count_label' => sprintf(_n('%d story', '%d stories', $total, 'yourstoryz'), $total),
            'feature' => $featured === null ? null : StoryCard::fromPost($featured) + [
                'excerpt' => wp_trim_words(wp_strip_all_tags(get_the_excerpt($featured)), self::EXCERPT_WORDS),
                'tags' => StoryCard::tags($featured->ID),
            ],
            'stories' => array_map(StoryCard::fromPost(...), $posts),
            'pagination' => (array) paginate_links([
                'type' => 'array',
                'prev_text' => '<span aria-hidden="true">&larr;</span><span class="ys-pagination__label">'.esc_html__('Vorige', 'yourstoryz').'</span>',
                'next_text' => '<span class="ys-pagination__label">'.esc_html__('Volgende', 'yourstoryz').'</span><span aria-hidden="true">&rarr;</span>',
            ]),
        ]);

        return $this->wrap((string) ob_get_clean());
    }

    /**
     * One story as an article; the story block uses this too, with a level-2 heading and no back link.
     */
    public function article(WP_Post $story, int $headingLevel, string $backUrl = ''): string
    {
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

        $title = get_the_title($story);

        ob_start();
        $this->loader->part('story-article', [
            'post_id' => $story->ID,
            'title' => $title,
            'content' => $this->withoutRepeatedTitle((string) $content, $title),
            'heading_level' => $headingLevel,
            'back_url' => $backUrl,
        ]);

        return (string) ob_get_clean();
    }

    /**
     * Imported stories often open with a heading that repeats the title; the article already shows the title.
     */
    private function withoutRepeatedTitle(string $content, string $title): string
    {
        if (preg_match('#^\s*<h[1-3][^>]*>(.*?)</h[1-3]>#is', $content, $match) !== 1) {
            return $content;
        }

        $heading = trim(html_entity_decode(wp_strip_all_tags($match[1]), ENT_QUOTES));

        return strcasecmp($heading, trim(html_entity_decode($title, ENT_QUOTES))) === 0
            ? substr($content, strlen($match[0]))
            : $content;
    }

    /**
     * @return list<WP_Post>
     */
    private function related(int $storyId): array
    {
        $posts = (new WP_Query([
            'post_type' => PostType::NAME,
            'post_status' => 'publish',
            'posts_per_page' => self::RELATED_COUNT,
            'post__not_in' => [$storyId],
            'no_found_rows' => true,
        ]))->posts;

        return array_values(array_filter($posts, fn (mixed $post): bool => $post instanceof WP_Post));
    }

    private function wrap(string $html): string
    {
        return '<div class="yourstoryz ys:mx-auto ys:w-full ys:max-w-6xl ys:px-4 ys:py-12 ys:box-border ys:md:py-16">'.$html.'</div>';
    }

    private function isEditing(): bool
    {
        return $this->editing ?? EditorContext::isBlockRenderer();
    }
}
