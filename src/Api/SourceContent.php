<?php

declare(strict_types=1);

namespace YourStoryz\Api;

final readonly class SourceContent
{
    /**
     * @param  list<string>  $hashtags
     * @param  list<string>  $selectedHashtags
     */
    public function __construct(
        public int $id,
        public string $lang,
        public string $type,
        public string $title,
        public string $content,
        public array $hashtags = [],
        public array $selectedHashtags = [],
    ) {}
}
