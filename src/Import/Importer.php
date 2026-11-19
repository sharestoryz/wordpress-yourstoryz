<?php

declare(strict_types=1);

namespace YourStoryz\Import;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use WP_Error;
use YourStoryz\Api\SourceStory;
use YourStoryz\PostType;
use YourStoryz\Settings;

/**
 * Writes the posts planned from source stories. Takes stories (not the catalog) so the caller
 * decides which stories to import. Keeps per-run state: not re-entrant.
 */
final class Importer
{
    /** @var array<string, true> */
    private array $existing = [];

    /** @var array<int, int|null> video id => attachment id, null when the sideload failed */
    private array $attachments = [];

    private int $created = 0;

    private int $skipped = 0;

    private int $failed = 0;

    /** @var list<string> */
    private array $messages = [];

    public function __construct(
        private readonly PostPlanner $planner = new PostPlanner,
        private readonly Settings $settings = new Settings,
        private readonly DeletedStories $deletedStories = new DeletedStories,
    ) {}

    /**
     * @param  list<SourceStory>  $stories
     */
    public function import(array $stories): ImportResult
    {
        $this->created = 0;
        $this->skipped = 0;
        $this->failed = 0;
        $this->messages = [];
        $this->existing = $this->existingPosts() + array_fill_keys($this->deletedStories->keys(), true);
        $this->attachments = $this->existingAttachments();

        foreach ($stories as $story) {
            try {
                $drafts = $this->planner->plan($story);
            } catch (Throwable $throwable) {
                $this->fail(sprintf(__('Story %d: %s', 'yourstoryz'), $story->id, $throwable->getMessage()));

                continue;
            }

            foreach ($drafts as $draft) {
                $this->importDraft($story, $draft);
            }
        }

        return new ImportResult($this->created, $this->skipped, $this->failed, $this->messages);
    }

    private function importDraft(SourceStory $story, PostDraft $draft): void
    {
        $key = $draft->meta['_ysz_source_id'].'|'.$draft->meta['_ysz_lang'];

        if (isset($this->existing[$key])) {
            $this->skipped++;

            return;
        }

        try {
            $postId = wp_insert_post(wp_slash($this->postArguments($draft)), true);

            if ($postId instanceof WP_Error) {
                $this->fail(sprintf(__('Story %d (%s): %s', 'yourstoryz'), $story->id, $draft->lang, $postId->get_error_message()));

                return;
            }

            $this->existing[$key] = true;
            $this->created++;

            $termResult = wp_set_post_terms($postId, $draft->tags, 'post_tag');

            if ($termResult instanceof WP_Error) {
                $this->messages[] = sprintf(__('Story %d (%s): tags niet opgeslagen: %s', 'yourstoryz'), $story->id, $draft->lang, $termResult->get_error_message());
            }

            $this->attachThumbnail($story, $draft, $postId);
        } catch (Throwable $throwable) {
            $this->fail(sprintf(__('Story %d (%s): %s', 'yourstoryz'), $story->id, $draft->lang, $throwable->getMessage()));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function postArguments(PostDraft $draft): array
    {
        $arguments = [
            'post_type' => PostType::NAME,
            'post_status' => $this->settings->importStatus(),
            'post_title' => wp_strip_all_tags($draft->title),
            'post_content' => wp_kses_post($draft->content),
            'meta_input' => $draft->meta,
        ];

        if ($draft->date === '') {
            return $arguments;
        }

        try {
            $date = new DateTimeImmutable($draft->date);
        } catch (Throwable) {
            return $arguments;
        }

        $arguments['post_date'] = $date->setTimezone(wp_timezone())->format('Y-m-d H:i:s');
        $arguments['post_date_gmt'] = $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        return $arguments;
    }

    private function attachThumbnail(SourceStory $story, PostDraft $draft, int $postId): void
    {
        $videoId = $story->video->id;

        if (! array_key_exists($videoId, $this->attachments)) {
            $this->attachments[$videoId] = $this->sideload($story, $draft, $postId);
        }

        if ($this->attachments[$videoId] !== null) {
            set_post_thumbnail($postId, $this->attachments[$videoId]);
        }
    }

    private function sideload(SourceStory $story, PostDraft $draft, int $postId): ?int
    {
        if (! function_exists('media_sideload_image')) {
            require_once ABSPATH.'wp-admin/includes/media.php';
            require_once ABSPATH.'wp-admin/includes/file.php';
            require_once ABSPATH.'wp-admin/includes/image.php';
        }

        try {
            $attachmentId = media_sideload_image($story->video->thumbnailUrl, $postId, $draft->title, 'id');
        } catch (Throwable $throwable) {
            $attachmentId = new WP_Error('sideload_exception', $throwable->getMessage());
        }

        if ($attachmentId instanceof WP_Error) {
            $this->messages[] = sprintf(__('Story %d (%s): thumbnail niet opgehaald: %s', 'yourstoryz'), $story->id, $draft->lang, $attachmentId->get_error_message());

            return null;
        }

        update_post_meta($attachmentId, '_ysz_video_id', (string) $story->video->id);

        return (int) $attachmentId;
    }

    private function fail(string $message): void
    {
        $this->failed++;
        $this->messages[] = $message;
    }

    /**
     * One query for every story post of any status except auto-draft (trash included, so a
     * trashed post is not recreated).
     *
     * @return array<string, true>
     */
    private function existingPosts(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT source.meta_value AS source_id, lang.meta_value AS lang
            FROM {$wpdb->posts} AS posts
            INNER JOIN {$wpdb->postmeta} AS source ON source.post_id = posts.ID AND source.meta_key = '_ysz_source_id'
            INNER JOIN {$wpdb->postmeta} AS lang ON lang.post_id = posts.ID AND lang.meta_key = '_ysz_lang'
            WHERE posts.post_type = %s AND posts.post_status != 'auto-draft'",
            PostType::NAME,
        ));

        $existing = [];

        foreach ((array) $rows as $row) {
            $existing[$row->source_id.'|'.$row->lang] = true;
        }

        return $existing;
    }

    /**
     * @return array<int, int>
     */
    private function existingAttachments(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT video.meta_value AS video_id, MIN(posts.ID) AS attachment_id
            FROM {$wpdb->posts} AS posts
            INNER JOIN {$wpdb->postmeta} AS video ON video.post_id = posts.ID AND video.meta_key = '_ysz_video_id'
            WHERE posts.post_type = 'attachment'
            GROUP BY video.meta_value",
        );

        $attachments = [];

        foreach ((array) $rows as $row) {
            $attachments[(int) $row->video_id] = (int) $row->attachment_id;
        }

        return $attachments;
    }
}
