<?php

declare(strict_types=1);

namespace YourStoryz\Api;

final readonly class SourceVideo
{
    /**
     * @param  list<SourceContent>  $contents
     */
    public function __construct(
        public int $id,
        public ?string $orientation,
        public string $videoUrl,
        public string $thumbnailUrl,
        public array $contents = [],
    ) {}
}
