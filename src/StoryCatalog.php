<?php

declare(strict_types=1);

namespace YourStoryz;

use YourStoryz\Api\ApiException;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Api\SourceStory;
use YourStoryz\Api\StoryMapper;

final class StoryCatalog
{
    public const TTL = 600;

    /**
     * Part of the transient key. Raise it when the cached payload changes shape, so older rows are ignored.
     */
    private const CACHE_VERSION = 2;

    public function __construct(
        private readonly GatewayProvider $gateways,
        private readonly StoryMapper $mapper = new StoryMapper,
    ) {}

    /**
     * Caches the raw API payload (plain arrays), not mapped objects: serialized objects break when their class changes.
     *
     * @return array{stories: list<SourceStory>, skipped: int}
     *
     * @throws ApiException
     */
    public function load(Source $source): array
    {
        $key = $this->key($source);
        $raw = get_transient($key);

        if (! is_array($raw)) {
            $raw = $this->gateways->gateway()->stories($source);

            set_transient($key, $raw, self::TTL);
        }

        $stories = array_values(array_filter(array_map($this->mapper->map(...), $raw)));

        return ['stories' => $stories, 'skipped' => count($raw) - count($stories)];
    }

    public function refresh(Source $source): void
    {
        delete_transient($this->key($source));
    }

    private function key(Source $source): string
    {
        return sprintf(
            'yourstoryz_stories_v%d_%s_%d_%d',
            self::CACHE_VERSION,
            $this->gateways->tokenHash(),
            $source->companyId,
            $source->departmentId ?? 0,
        );
    }
}
