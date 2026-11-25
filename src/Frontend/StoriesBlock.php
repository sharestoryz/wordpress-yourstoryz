<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use WP_Query;
use YourStoryz\PostType;

final class StoriesBlock
{
    private const DEFAULT_COUNT = 6;

    private const DEFAULT_COLUMNS = 3;

    private const MAX_COUNT = 24;

    private const MAX_COLUMNS = 4;

    private const COLUMN_CLASSES = [
        1 => 'ys:md:grid-cols-1',
        2 => 'ys:sm:grid-cols-2 ys:md:grid-cols-2',
        3 => 'ys:sm:grid-cols-2 ys:md:grid-cols-3',
        4 => 'ys:sm:grid-cols-2 ys:md:grid-cols-4',
    ];

    public function __construct(
        private readonly TemplateLoader $loader = new TemplateLoader,
        private readonly string $directory = YOURSTORYZ_DIR,
    ) {}

    public function register(): void
    {
        add_action('init', [$this, 'registerBlock']);
    }

    public function registerBlock(): void
    {
        register_block_type($this->directory.'blocks/stories', [
            'render_callback' => [$this, 'render'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function render(array $attributes = []): string
    {
        $count = max(1, min(self::MAX_COUNT, (int) ($attributes['count'] ?? self::DEFAULT_COUNT)));
        $columns = max(1, min(self::MAX_COLUMNS, (int) ($attributes['columns'] ?? self::DEFAULT_COLUMNS)));
        $tag = sanitize_title((string) ($attributes['tag'] ?? ''));
        $showArchiveLink = (bool) ($attributes['showArchiveLink'] ?? true);

        $queryArgs = [
            'post_type' => PostType::NAME,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => $count,
            'no_found_rows' => true,
        ];

        if ($tag !== '') {
            $queryArgs['tag'] = $tag;
        }

        $stories = (new WP_Query($queryArgs))->posts;

        ob_start();

        if ($stories === []) {
            echo '<p>'.esc_html__('Er zijn nog geen stories.', 'yourstoryz').'</p>';
        } else {
            $this->loader->part('story-grid', [
                'stories' => array_map(StoryCard::fromPost(...), $stories),
                'column_class' => self::COLUMN_CLASSES[$columns],
            ]);

            wp_enqueue_style(Assets::HANDLE);
        }

        $archiveLink = $showArchiveLink ? get_post_type_archive_link(PostType::NAME) : false;

        if ($archiveLink !== false) {
            echo '<p class="ys:mt-6"><a href="'.esc_url($archiveLink).'">'.esc_html__('Alle stories bekijken', 'yourstoryz').'</a></p>';
        }

        $content = (string) ob_get_clean();

        return sprintf('<div %s>%s</div>', get_block_wrapper_attributes(), $content);
    }
}
