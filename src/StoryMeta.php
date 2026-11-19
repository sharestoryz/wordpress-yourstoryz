<?php

declare(strict_types=1);

namespace YourStoryz;

/**
 * Post meta keys written on imported story posts, and the identity key built from them.
 */
final class StoryMeta
{
    public const SOURCE_ID = '_ysz_source_id';

    public const LANG = '_ysz_lang';

    public const CONTENT_ID = '_ysz_content_id';

    public const VIDEO_ID = '_ysz_video_id';

    public const VIDEO_URL = '_ysz_video_url';

    public const ORIENTATION = '_ysz_orientation';

    public const AUTHOR = '_ysz_author';

    public const UPDATED_AT = '_ysz_updated_at';

    /**
     * Identifies one imported post: a source story in one language.
     */
    public static function identity(string $sourceId, string $lang): string
    {
        return $sourceId.'|'.$lang;
    }
}
