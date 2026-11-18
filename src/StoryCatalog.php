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

    public function __construct(
        private readonly GatewayProvider $gateways,
        private readonly StoryMapper $mapper = new StoryMapper,
    ) {}

    /**
     * @return array{stories: list<SourceStory>, skipped: int}
     *
     * @throws ApiException
     */
    public function load(Source $source): array
    {
        $key = $this->key($source);
        $cached = get_transient($key);

        if (is_array($cached) && isset($cached['stories'], $cached['skipped'])) {
            return $cached;
        }

        $raw = $this->gateways->gateway()->stories($source);

        $stories = array_values(array_filter(array_map($this->mapper->map(...), $raw)));
        $result = ['stories' => $stories, 'skipped' => count($raw) - count($stories)];

        set_transient($key, $result, self::TTL);

        return $result;
    }

    public function refresh(Source $source): void
    {
        delete_transient($this->key($source));
    }

    private function key(Source $source): string
    {
        return sprintf(
            'yourstoryz_stories_%s_%d_%d',
            $this->gateways->tokenHash(),
            $source->companyId,
            $source->departmentId ?? 0,
        );
    }
}
