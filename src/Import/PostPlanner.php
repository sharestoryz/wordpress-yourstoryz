<?php

declare(strict_types=1);

namespace YourStoryz\Import;

use YourStoryz\Api\SourceContent;
use YourStoryz\Api\SourceStory;
use YourStoryz\StoryMeta;

final class PostPlanner
{
    private const DEFAULT_LANG = 'nl';

    public function __construct(private readonly MarkdownToBlocks $markdown = new MarkdownToBlocks) {}

    /**
     * @return list<PostDraft>
     */
    public function plan(SourceStory $story): array
    {
        $news = array_values(array_filter(
            $story->video->contents,
            fn (SourceContent $content): bool => $content->type === 'news',
        ));

        $drafts = array_map(
            fn (SourceContent $content): PostDraft => $this->newsDraft($story, $content),
            $news,
        );

        $hasDutchNews = array_filter(
            $news,
            fn (SourceContent $content): bool => $content->lang === self::DEFAULT_LANG,
        ) !== [];

        if (! $hasDutchNews) {
            array_unshift($drafts, $this->generalDraft($story));
        }

        return $drafts;
    }

    private function newsDraft(SourceStory $story, SourceContent $content): PostDraft
    {
        return new PostDraft(
            title: $content->title,
            content: $this->markdown->fromMarkdown($content->content),
            tags: $this->tags($content),
            meta: $this->meta($story, $content->lang, (string) $content->id),
            date: $content->createdAt,
            lang: $content->lang,
            contentId: $content->id,
        );
    }

    private function generalDraft(SourceStory $story): PostDraft
    {
        return new PostDraft(
            title: $story->title,
            content: $this->markdown->fromPlainText($story->description),
            tags: [self::DEFAULT_LANG],
            meta: $this->meta($story, self::DEFAULT_LANG, ''),
            date: $story->createdAt,
            lang: self::DEFAULT_LANG,
            contentId: null,
        );
    }

    /**
     * @return list<string>
     */
    private function tags(SourceContent $content): array
    {
        $hashtags = array_map(
            fn (string $hashtag): string => trim(ltrim(trim($hashtag), '#')),
            $content->selectedHashtags,
        );

        return array_values(array_unique(array_filter([$content->lang, ...$hashtags])));
    }

    /**
     * @return array<string, string>
     */
    private function meta(SourceStory $story, string $lang, string $contentId): array
    {
        return [
            StoryMeta::SOURCE_ID => (string) $story->id,
            StoryMeta::LANG => $lang,
            StoryMeta::CONTENT_ID => $contentId,
            StoryMeta::VIDEO_ID => (string) $story->video->id,
            StoryMeta::VIDEO_URL => $story->video->videoUrl,
            StoryMeta::ORIENTATION => $story->video->orientation ?? '',
            StoryMeta::AUTHOR => $story->author ?? '',
            StoryMeta::UPDATED_AT => $story->updatedAt,
        ];
    }
}
