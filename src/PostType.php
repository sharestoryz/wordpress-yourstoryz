<?php

declare(strict_types=1);

namespace YourStoryz;

use YourStoryz\Admin\Menu;

final class PostType
{
    public const NAME = 'yourstoryz_story';

    public static function activate(): void
    {
        (new self)->registerPostType();

        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        unregister_post_type(self::NAME);

        flush_rewrite_rules();
    }

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
    }

    public function registerPostType(): void
    {
        register_post_type(self::NAME, [
            'labels' => [
                'name' => __('Stories', 'yourstoryz'),
                'singular_name' => __('Story', 'yourstoryz'),
                'add_new_item' => __('Nieuwe story toevoegen', 'yourstoryz'),
                'edit_item' => __('Story bewerken', 'yourstoryz'),
                'new_item' => __('Nieuwe story', 'yourstoryz'),
                'view_item' => __('Story bekijken', 'yourstoryz'),
                'search_items' => __('Stories zoeken', 'yourstoryz'),
                'not_found' => __('Geen stories gevonden', 'yourstoryz'),
                'not_found_in_trash' => __('Geen stories gevonden in de prullenbak', 'yourstoryz'),
                'all_items' => __('Alle stories', 'yourstoryz'),
                'menu_name' => __('Stories', 'yourstoryz'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'show_in_menu' => Menu::SLUG,
            'rewrite' => ['slug' => 'stories'],
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
            'taxonomies' => ['post_tag'],
        ]);

        register_taxonomy_for_object_type('post_tag', self::NAME);
    }
}
