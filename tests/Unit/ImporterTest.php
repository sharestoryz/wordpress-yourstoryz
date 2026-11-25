<?php

use Brain\Monkey\Functions;
use YourStoryz\Api\SourceContent;
use YourStoryz\Api\SourceStory;
use YourStoryz\Api\SourceVideo;
use YourStoryz\Import\Importer;

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct(public string $code = '', public string $message = '') {}

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}

function importerStory(int $id, int $videoId, array $langs = ['nl']): SourceStory
{
    $contents = array_map(
        fn (string $lang): SourceContent => new SourceContent($id * 10, $lang, 'news', "Titel {$id} {$lang}", 'Tekst', [], [], '2025-02-01T10:00:00.000000Z'),
        $langs,
    );

    return new SourceStory(
        id: $id,
        title: 'Algemeen',
        description: 'Omschrijving',
        video: new SourceVideo($videoId, null, 'https://example.com/v.mp4', "https://example.com/{$videoId}.jpg", $contents),
        author: null,
        createdAt: '2025-01-01T00:00:00.000000Z',
        updatedAt: '2025-01-02T00:00:00.000000Z',
    );
}

beforeEach(function () {
    $this->queries = 0;
    $this->postRows = [];
    $this->attachmentRows = [];
    $this->inserted = [];
    $this->thumbnails = [];
    $this->attachmentMeta = [];
    $this->sideloads = [];
    $this->status = 'draft';
    $this->deleted = [];
    $this->terms = [];
    $this->legacy = [];

    $test = $this;
    $GLOBALS['wpdb'] = new class($test)
    {
        public string $posts = 'wp_posts';

        public string $postmeta = 'wp_postmeta';

        public function __construct(private $test) {}

        public function prepare(string $query, ...$args): string
        {
            return $query;
        }

        public function get_results(string $query): array
        {
            $this->test->queries++;

            return str_contains($query, "'attachment'") ? $this->test->attachmentRows : $this->test->postRows;
        }
    };

    Functions\when('__')->returnArg(1);
    Functions\when('wp_slash')->returnArg(1);
    Functions\when('wp_strip_all_tags')->alias(fn ($text) => strip_tags($text));
    Functions\when('wp_kses_post')->returnArg(1);
    Functions\when('wp_timezone')->alias(fn () => new DateTimeZone('Europe/Amsterdam'));
    Functions\when('get_option')->alias(fn ($key, $default = false) => match ($key) {
        'yourstoryz_deleted_stories' => $this->deleted,
        'yourstoryz_legacy_imports' => $this->legacy,
        default => $this->status,
    });
    Functions\when('wp_set_post_terms')->alias(function (int $postId, array $tags, string $taxonomy) {
        $this->terms[$postId] = [$tags, $taxonomy];

        return [];
    });
    Functions\when('wp_insert_post')->alias(function (array $arguments, bool $wpError = false) {
        $this->inserted[] = $arguments;

        return 100 + count($this->inserted);
    });
    Functions\when('set_post_thumbnail')->alias(function (int $postId, int $attachmentId) {
        $this->thumbnails[$postId] = $attachmentId;

        return true;
    });
    Functions\when('update_post_meta')->alias(function (int $id, string $key, string $value) {
        $this->attachmentMeta[$id] = [$key, $value];

        return true;
    });
    Functions\when('media_sideload_image')->alias(function (string $url, int $postId, string $title, string $return) {
        $this->sideloads[] = [$url, $postId, $return];

        return 500 + count($this->sideloads);
    });
});

it('creates a post per planned draft with type, status, dates and meta', function () {
    $result = (new Importer)->import([importerStory(7, 3, ['nl'])]);

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and($result->failed)->toBe(0)
        ->and($this->inserted)->toHaveCount(1);

    $arguments = $this->inserted[0];

    expect($arguments['post_type'])->toBe('yourstoryz_story')
        ->and($arguments['post_status'])->toBe('draft')
        ->and($arguments['post_title'])->toBe('Titel 7 nl')
        ->and($arguments['post_date_gmt'])->toBe('2025-02-01 10:00:00')
        ->and($arguments['post_date'])->toBe('2025-02-01 11:00:00')
        ->and($arguments['meta_input']['_ysz_source_id'])->toBe('7')
        ->and($arguments['meta_input']['_ysz_lang'])->toBe('nl');
});

it('sets terms on the post_tag taxonomy', function () {
    (new Importer)->import([importerStory(7, 3)]);

    expect($this->terms)->toBe([101 => [['nl'], 'post_tag']]);
});

it('leaves the date out when it is empty or invalid', function (string $createdAt) {
    $story = importerStory(7, 3, []);

    (new Importer)->import([new SourceStory($story->id, $story->title, $story->description, $story->video, null, $createdAt, $story->updatedAt)]);

    expect($this->inserted[0])->not->toHaveKey('post_date')
        ->and($this->inserted[0])->not->toHaveKey('post_date_gmt');
})->with(['', 'geen datum']);

it('uses the configured status and falls back to draft for unknown values', function (string $configured, string $expected) {
    $this->status = $configured;

    (new Importer)->import([importerStory(7, 3)]);

    expect($this->inserted[0]['post_status'])->toBe($expected);
})->with([
    ['publish', 'publish'],
    ['pending', 'draft'],
    ['private', 'draft'],
    ['', 'draft'],
]);

it('skips existing posts without writing and reads existing posts and attachments in two queries', function () {
    $this->postRows = [(object) ['source_id' => '7', 'lang' => 'nl']];

    $result = (new Importer)->import([importerStory(7, 3), importerStory(8, 4), importerStory(9, 5)]);

    expect($result->created)->toBe(2)
        ->and($result->skipped)->toBe(1)
        ->and($this->inserted)->toHaveCount(2)
        ->and($this->queries)->toBe(2);
});

it('creates a missing language of an already imported story', function () {
    $this->postRows = [(object) ['source_id' => '7', 'lang' => 'nl']];

    $result = (new Importer)->import([importerStory(7, 3, ['nl', 'en'])]);

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(1)
        ->and($this->inserted[0]['meta_input']['_ysz_lang'])->toBe('en');
});

it('creates nothing on a second run over the same stories', function () {
    $stories = [importerStory(7, 3, ['nl', 'en'])];

    (new Importer)->import($stories);
    $this->postRows = array_map(
        fn (array $arguments) => (object) ['source_id' => $arguments['meta_input']['_ysz_source_id'], 'lang' => $arguments['meta_input']['_ysz_lang']],
        $this->inserted,
    );
    $second = (new Importer)->import($stories);

    expect($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2);
});

it('does not create the same language twice within one run', function () {
    $result = (new Importer)->import([importerStory(7, 3), importerStory(7, 3)]);

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(1);
});

it('sideloads the thumbnail once per story and reuses it for other languages', function () {
    $result = (new Importer)->import([importerStory(7, 3, ['nl', 'en'])]);

    expect($result->created)->toBe(2)
        ->and($this->sideloads)->toHaveCount(1)
        ->and($this->sideloads[0])->toBe(['https://example.com/3.jpg', 101, 'id'])
        ->and($this->attachmentMeta[501])->toBe(['_ysz_video_id', '3'])
        ->and($this->thumbnails)->toBe([101 => 501, 102 => 501]);
});

it('reuses an attachment that already exists for the video', function () {
    $this->attachmentRows = [(object) ['video_id' => '3', 'attachment_id' => '42']];

    (new Importer)->import([importerStory(7, 3)]);

    expect($this->sideloads)->toBe([])
        ->and($this->thumbnails)->toBe([101 => 42]);
});

it('does not sideload for a story whose posts all exist', function () {
    $this->postRows = [(object) ['source_id' => '7', 'lang' => 'nl']];

    (new Importer)->import([importerStory(7, 3)]);

    expect($this->sideloads)->toBe([]);
});

it('keeps the post without featured image and reports a message when the sideload fails', function () {
    Functions\when('media_sideload_image')->alias(function () {
        $this->sideloads[] = true;

        return new WP_Error('http', 'Download mislukt');
    });

    $result = (new Importer)->import([importerStory(7, 3, ['nl', 'en'])]);

    expect($result->created)->toBe(2)
        ->and($result->failed)->toBe(0)
        ->and($this->thumbnails)->toBe([])
        ->and($this->sideloads)->toHaveCount(1)
        ->and($result->messages)->toHaveCount(1)
        ->and($result->messages[0])->toContain('Download mislukt');
});

it('counts a WP_Error on insert as failed and continues with the next story', function () {
    Functions\when('wp_insert_post')->alias(function (array $arguments) {
        if ($arguments['meta_input']['_ysz_source_id'] === '7') {
            return new WP_Error('db', 'Kon niet opslaan');
        }
        $this->inserted[] = $arguments;

        return 100 + count($this->inserted);
    });

    $result = (new Importer)->import([importerStory(7, 3), importerStory(8, 4)]);

    expect($result->created)->toBe(1)
        ->and($result->failed)->toBe(1)
        ->and($result->messages[0])->toContain('Kon niet opslaan')
        ->and($this->inserted)->toHaveCount(1);
});

it('counts an exception as failed and continues', function () {
    Functions\when('wp_insert_post')->alias(function (array $arguments) {
        if ($arguments['meta_input']['_ysz_source_id'] === '7') {
            throw new RuntimeException('Boom');
        }
        $this->inserted[] = $arguments;

        return 100 + count($this->inserted);
    });

    $result = (new Importer)->import([importerStory(7, 3), importerStory(8, 4)]);

    expect($result->created)->toBe(1)
        ->and($result->failed)->toBe(1)
        ->and($result->messages[0])->toContain('Boom');
});

it('skips a story whose key is in the deleted list', function () {
    $this->deleted = ['7|nl'];

    $result = (new Importer)->import([importerStory(7, 3, ['nl', 'en']), importerStory(8, 4)]);

    expect($result->created)->toBe(2)
        ->and($result->skipped)->toBe(1)
        ->and($this->queries)->toBe(2)
        ->and(array_map(fn (array $arguments) => $arguments['meta_input']['_ysz_source_id'].'|'.$arguments['meta_input']['_ysz_lang'], $this->inserted))->toBe(['7|en', '8|nl']);
});

it('skips every language of a story whose story id or video id is in the legacy registry', function (array $legacy) {
    $this->legacy = $legacy;

    $result = (new Importer)->import([importerStory(7, 3, ['nl', 'en']), importerStory(8, 4)]);

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(2)
        ->and($this->inserted)->toHaveCount(1)
        ->and($this->inserted[0]['meta_input']['_ysz_source_id'])->toBe('8')
        ->and($this->sideloads)->toHaveCount(1);
})->with([
    'story id' => [['story_ids' => [7]]],
    'video id' => [['video_ids' => [3]]],
]);

it('skips only the draft whose content id is in the legacy registry', function () {
    $this->legacy = ['content_ids' => [70]];

    $result = (new Importer)->import([importerStory(7, 3, ['nl']), importerStory(8, 4)]);

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(1)
        ->and($this->inserted[0]['meta_input']['_ysz_source_id'])->toBe('8');
});
