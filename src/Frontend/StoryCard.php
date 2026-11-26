<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use WP_Post;
use WP_Term;
use YourStoryz\StoryMeta;

final class StoryCard
{
    /**
     * @return array{url: string, title: string, thumbnail_url: string, date: string, date_iso: string, lang: string, author: string, orientation: string}
     */
    public static function fromPost(WP_Post $post): array
    {
        return [
            'url' => (string) get_permalink($post),
            'title' => get_the_title($post),
            'thumbnail_url' => (string) get_the_post_thumbnail_url(
                $post,
                'medium_large',
            ),
            'date' => (string) get_the_date('', $post),
            'date_iso' => (string) get_the_date('c', $post),
            'lang' => strtoupper((string) get_post_meta($post->ID, StoryMeta::LANG, true)),
            'author' => (string) get_post_meta($post->ID, StoryMeta::AUTHOR, true),
            'orientation' => self::orientation($post->ID),
        ];
    }

    /**
     * The story's tags as links, without the language tag the importer adds: the language label shows that already.
     *
     * @return list<array{name: string, url: string}>
     */
    public static function tags(int $postId): array
    {
        $lang = (string) get_post_meta($postId, StoryMeta::LANG, true);
        $tags = array_filter(get_the_tags($postId) ?: [], fn (WP_Term $tag): bool => strcasecmp($tag->name, $lang) !== 0);

        return array_values(array_map(
            fn (WP_Term $tag): array => ['name' => $tag->name, 'url' => (string) get_tag_link($tag)],
            $tags,
        ));
    }

    public static function orientation(int $postId): string
    {
        return get_post_meta($postId, StoryMeta::ORIENTATION, true) === 'vertical' ? 'vertical' : 'horizontal';
    }
}
