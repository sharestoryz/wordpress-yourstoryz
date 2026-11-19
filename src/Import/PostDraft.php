<?php

declare(strict_types=1);

namespace YourStoryz\Import;

final readonly class PostDraft
{
    /**
     * @param  list<string>  $tags
     * @param  array<string, string>  $meta
     */
    public function __construct(
        public string $title,
        public string $content,
        public array $tags,
        public array $meta,
        public string $date,
        public string $lang,
        public ?int $contentId,
    ) {}
}
