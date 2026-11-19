<?php

declare(strict_types=1);

namespace YourStoryz;

final readonly class Source
{
    public function __construct(
        public int $companyId,
        public ?int $departmentId = null,
    ) {}
}
