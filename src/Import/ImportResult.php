<?php

declare(strict_types=1);

namespace YourStoryz\Import;

final readonly class ImportResult
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        public int $created = 0,
        public int $skipped = 0,
        public int $failed = 0,
        public array $messages = [],
    ) {}
}
