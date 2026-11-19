<?php

declare(strict_types=1);

namespace YourStoryz\Import;

use WP_Post;
use YourStoryz\PostType;

/**
 * Remembers the source|lang key of permanently deleted story posts, so the importer does not recreate them
 * once the trash is emptied.
 */
final class DeletedStories
{
    public const OPTION = 'yourstoryz_deleted_stories';

    public function register(): void
    {
        add_action('before_delete_post', [$this, 'record'], 10, 2);
    }

    public function record(int $postId, WP_Post $post): void
    {
        if ($post->post_type !== PostType::NAME) {
            return;
        }

        $sourceId = (string) get_post_meta($postId, '_ysz_source_id', true);
        $lang = (string) get_post_meta($postId, '_ysz_lang', true);

        if ($sourceId === '' || $lang === '') {
            return;
        }

        $keys = $this->keys();
        $key = $sourceId.'|'.$lang;

        if (in_array($key, $keys, true)) {
            return;
        }

        $keys[] = $key;

        update_option(self::OPTION, $keys, false);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        $keys = get_option(self::OPTION, []);

        return is_array($keys) ? array_values(array_filter($keys, 'is_string')) : [];
    }
}
