<?php

declare(strict_types=1);

namespace YourStoryz\Import;

/**
 * Ids of stories that 1.3.2 and 2.0.x already imported as ordinary posts. The importer skips them so they do not
 * come in twice. Filled once by the upgrader; the posts themselves are never touched.
 */
final class LegacyImports
{
    public const OPTION = 'yourstoryz_legacy_imports';

    private const LISTS = ['story_ids', 'video_ids', 'content_ids'];

    /**
     * @return array{story_ids: list<int>, video_ids: list<int>, content_ids: list<int>}
     */
    public function all(): array
    {
        $saved = get_option(self::OPTION, []);
        $saved = is_array($saved) ? $saved : [];

        return [
            'story_ids' => $this->normalize($saved['story_ids'] ?? []),
            'video_ids' => $this->normalize($saved['video_ids'] ?? []),
            'content_ids' => $this->normalize($saved['content_ids'] ?? []),
        ];
    }

    public function contains(int $storyId, int $videoId, ?int $contentId): bool
    {
        $ids = $this->all();

        return in_array($storyId, $ids['story_ids'], true)
            || in_array($videoId, $ids['video_ids'], true)
            || ($contentId !== null && in_array($contentId, $ids['content_ids'], true));
    }

    /**
     * Adds ids to the registry, keeping each list unique.
     *
     * @param  array<mixed>  $storyIds
     * @param  array<mixed>  $videoIds
     * @param  array<mixed>  $contentIds
     */
    public function merge(array $storyIds = [], array $videoIds = [], array $contentIds = []): void
    {
        $current = $this->all();

        update_option(self::OPTION, [
            'story_ids' => $this->normalize([...$current['story_ids'], ...$storyIds]),
            'video_ids' => $this->normalize([...$current['video_ids'], ...$videoIds]),
            'content_ids' => $this->normalize([...$current['content_ids'], ...$contentIds]),
        ], false);
    }

    /**
     * @return list<int>
     */
    private function normalize(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        $ints = array_map('intval', array_filter($ids, fn (mixed $id): bool => is_int($id) || (is_string($id) && ctype_digit($id))));

        return array_values(array_unique(array_filter($ints, fn (int $id): bool => $id > 0)));
    }
}
