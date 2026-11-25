<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

final class EditorContext
{
    private const BLOCK_RENDERER_ROUTE = '/wp/v2/block-renderer/';

    public static function isBlockRenderer(): bool
    {
        $route = (string) ($GLOBALS['wp']->query_vars['rest_route'] ?? '');

        return str_starts_with($route, self::BLOCK_RENDERER_ROUTE);
    }
}
