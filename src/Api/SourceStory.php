<?php

declare(strict_types=1);

namespace YourStoryz\Api;

final readonly class SourceStory
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public SourceVideo $video,
        public ?string $author,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
