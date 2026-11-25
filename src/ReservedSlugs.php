<?php

declare(strict_types=1);

namespace YourStoryz;

final class ReservedSlugs
{
    private const WORDPRESS_SLUGS = [
        'wp-admin',
        'wp-content',
        'wp-includes',
        'wp-json',
        'feed',
        'page',
        'tag',
        'category',
        'author',
        'search',
        'embed',
        'attachment',
        'comments',
    ];

    public function contains(string $slug): bool
    {
        $publicQueryVars = isset($GLOBALS['wp']) ? (array) ($GLOBALS['wp']->public_query_vars ?? []) : [];

        if (in_array($slug, [...self::WORDPRESS_SLUGS, ...$publicQueryVars], true)) {
            return true;
        }

        if (get_page_by_path($slug, 'OBJECT', ['page', 'post']) !== null) {
            return true;
        }

        $objects = [
            ...array_values(array_filter(get_post_types([], 'objects'), fn (object $type): bool => $type->name !== PostType::NAME)),
            ...array_values(get_taxonomies([], 'objects')),
        ];

        foreach ($objects as $object) {
            $rewriteSlug = is_array($object->rewrite ?? null) ? ($object->rewrite['slug'] ?? '') : '';

            if (in_array($slug, [$object->name, $rewriteSlug], true)) {
                return true;
            }
        }

        return false;
    }
}
