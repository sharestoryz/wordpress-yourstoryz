<?php

declare(strict_types=1);

namespace YourStoryz\Api;

final class StoryMapper
{
    /**
     * @param  array<string, mixed>  $story
     */
    public function map(array $story): ?SourceStory
    {
        $video = $story['video'] ?? null;

        if (! is_array($video)) {
            return null;
        }

        $author = $this->string(is_array($story['author'] ?? null) ? ($story['author']['name'] ?? null) : null);
        $orientation = $this->string($video['orientation'] ?? null);

        return new SourceStory(
            id: (int) ($story['id'] ?? 0),
            title: $this->string($story['title'] ?? null),
            description: $this->string($story['description'] ?? null),
            video: new SourceVideo(
                id: (int) ($video['id'] ?? 0),
                orientation: $orientation === '' ? null : $orientation,
                videoUrl: $this->string($video['video_url'] ?? null),
                thumbnailUrl: $this->string($video['thumbnail_url'] ?? null),
                contents: $this->contents($video['contents'] ?? null),
            ),
            author: $author === '' ? null : $author,
            createdAt: $this->string($story['created_at'] ?? null),
            updatedAt: $this->string($story['updated_at'] ?? null),
        );
    }

    /**
     * @return list<SourceContent>
     */
    private function contents(mixed $contents): array
    {
        $mapped = [];

        foreach (is_array($contents) ? $contents : [] as $content) {
            if (! is_array($content)) {
                continue;
            }

            $mapped[] = new SourceContent(
                id: (int) ($content['id'] ?? 0),
                lang: $this->string($content['lang'] ?? null),
                type: $this->string($content['type'] ?? null),
                title: $this->string($content['title'] ?? null),
                content: $this->string($content['content'] ?? null),
                hashtags: $this->strings($content['hashtags'] ?? null),
                selectedHashtags: $this->strings($content['selected_hashtags'] ?? null),
            );
        }

        return $mapped;
    }

    private function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $values): array
    {
        return is_array($values)
            ? array_values(array_filter(array_map(fn (mixed $value): string => $this->string($value), $values)))
            : [];
    }
}
