<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use WP_Post;

final class StoryCard
{
    /**
     * @return array{url: string, title: string, thumbnail_url: string}
     */
    public static function fromPost(WP_Post $post): array
    {
        return [
            'url' => (string) get_permalink($post),
            'title' => get_the_title($post),
            'thumbnail_url' => (string) get_the_post_thumbnail_url($post, 'medium_large'),
        ];
    }
}
