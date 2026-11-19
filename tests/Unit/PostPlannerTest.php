<?php

use YourStoryz\Api\SourceContent;
use YourStoryz\Api\SourceStory;
use YourStoryz\Api\SourceVideo;
use YourStoryz\Api\StoryMapper;
use YourStoryz\Import\MarkdownToBlocks;
use YourStoryz\Import\PostPlanner;

function plannerFixture(int $id): array
{
    foreach (json_decode(file_get_contents(__DIR__.'/../Fixtures/stories.json'), true) as $story) {
        if ($story['id'] === $id) {
            return $story;
        }
    }

    throw new RuntimeException("Fixture {$id} missing");
}

function plannerStory(array $contents, string $description = "Eerste alinea.\n\nTweede <b>alinea</b>\nmet regeleinde."): SourceStory
{
    return new SourceStory(
        id: 7,
        title: 'Algemene titel',
        description: $description,
        video: new SourceVideo(3, null, 'https://example.com/v.mp4', 'https://example.com/t.jpg', $contents),
        author: null,
        createdAt: '2025-01-01T00:00:00.000000Z',
        updatedAt: '2025-01-02T00:00:00.000000Z',
    );
}

function plannerContent(int $id, string $lang, string $type, array $selected = [], string $content = 'Tekst'): SourceContent
{
    return new SourceContent($id, $lang, $type, "Titel {$id}", $content, [], $selected, '2025-02-0'.$id.'T00:00:00.000000Z');
}

function plannedStory(int $id): array
{
    return (new PostPlanner)->plan((new StoryMapper)->map(plannerFixture($id)));
}

it('plans only the general Dutch draft for a story without contents', function () {
    $drafts = plannedStory(1023);

    expect($drafts)->toHaveCount(1)
        ->and($drafts[0]->lang)->toBe('nl')
        ->and($drafts[0]->tags)->toBe(['nl'])
        ->and($drafts[0]->contentId)->toBeNull()
        ->and($drafts[0]->title)->toBe('Voorbeeldverhaal 1023')
        ->and($drafts[0]->meta['_ysz_content_id'])->toBe('');
});

it('plans one draft per news item and no general draft when a Dutch news item exists', function () {
    $drafts = plannedStory(1313);

    expect($drafts)->toHaveCount(2)
        ->and(array_map(fn ($draft) => $draft->lang, $drafts))->toBe(['en', 'nl'])
        ->and(array_map(fn ($draft) => $draft->contentId, $drafts))->toBe([1210, 1211]);
});

it('ignores social contents and adds the general draft when no news exists', function () {
    $story = plannerStory([plannerContent(1, 'nl', 'social'), plannerContent(2, 'en', 'social')]);
    $drafts = (new PostPlanner)->plan($story);

    expect($drafts)->toHaveCount(1)
        ->and($drafts[0]->contentId)->toBeNull()
        ->and($drafts[0]->date)->toBe('2025-01-01T00:00:00.000000Z');
});

it('adds the general Dutch draft next to news in other languages only', function () {
    $drafts = (new PostPlanner)->plan(plannerStory([plannerContent(1, 'en', 'news'), plannerContent(2, 'nl', 'social')]));

    expect($drafts)->toHaveCount(2)
        ->and($drafts[0]->contentId)->toBeNull()
        ->and($drafts[1]->lang)->toBe('en');
});

it('builds tags from the language and the cleaned selected hashtags', function () {
    $drafts = (new PostPlanner)->plan(plannerStory([
        plannerContent(1, 'nl', 'news', ['#een', ' #twee ', '#een', '#', '', 'drie']),
    ]));

    expect($drafts[0]->tags)->toBe(['nl', 'een', 'twee', 'drie']);
});

it('does not use hashtags and keeps the draft fields from the content', function () {
    $content = new SourceContent(9, 'nl', 'news', 'Nieuws', 'Tekst', ['#nietgebruikt'], ['#wel'], '2025-03-03T00:00:00.000000Z');
    $draft = (new PostPlanner)->plan(plannerStory([$content]))[0];

    expect($draft->tags)->toBe(['nl', 'wel'])
        ->and($draft->title)->toBe('Nieuws')
        ->and($draft->date)->toBe('2025-03-03T00:00:00.000000Z')
        ->and($draft->contentId)->toBe(9);
});

it('fills the source meta', function () {
    $story = (new StoryMapper)->map(plannerFixture(1313));
    $draft = (new PostPlanner)->plan($story)[1];

    expect($draft->meta)->toBe([
        '_ysz_source_id' => '1313',
        '_ysz_lang' => 'nl',
        '_ysz_content_id' => '1211',
        '_ysz_video_id' => '1802',
        '_ysz_video_url' => 'https://example.com/video_url/1313.jpg',
        '_ysz_orientation' => 'horizontal',
        '_ysz_author' => 'Voorbeeldauteur',
        '_ysz_updated_at' => $story->updatedAt,
    ]);
});

it('uses empty meta values for a missing orientation and author', function () {
    $draft = (new PostPlanner)->plan(plannerStory([]))[0];

    expect($draft->meta['_ysz_orientation'])->toBe('')
        ->and($draft->meta['_ysz_author'])->toBe('');
});

it('writes the description as escaped paragraph blocks', function () {
    $draft = (new PostPlanner)->plan(plannerStory([]))[0];

    expect($draft->content)->toBe(
        "<!-- wp:paragraph -->\n<p>Eerste alinea.</p>\n<!-- /wp:paragraph -->\n\n"
        ."<!-- wp:paragraph -->\n<p>Tweede &lt;b&gt;alinea&lt;/b&gt;<br>met regeleinde.</p>\n<!-- /wp:paragraph -->"
    );
});

it('converts markdown paragraphs from the fixture to blocks', function () {
    $draft = plannedStory(1313)[1];

    expect($draft->content)->toBe(
        "<!-- wp:paragraph -->\n<p>Lorem <strong>ipsum</strong> dolor sit amet.</p>\n<!-- /wp:paragraph -->\n\n"
        ."<!-- wp:paragraph -->\n<p>Consectetur adipiscing elit.</p>\n<!-- /wp:paragraph -->"
    );
});

it('converts headings, lists and other elements', function () {
    $blocks = (new MarkdownToBlocks)->fromMarkdown(
        "# Een\n\n## Twee\n\n### Drie\n\n- a\n- b\n  - c\n\n1. x\n2. y\n\n> quote\n\n---"
    );

    expect($blocks)
        ->toContain("<!-- wp:heading {\"level\":1} -->\n<h1 class=\"wp-block-heading\">Een</h1>\n<!-- /wp:heading -->")
        ->toContain("<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Twee</h2>\n<!-- /wp:heading -->")
        ->toContain('<!-- wp:heading {"level":3} -->')
        ->toContain("<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>a</li>\n<!-- /wp:list-item -->")
        ->toContain("<li>b<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>c</li>")
        ->toContain('<!-- wp:list {"ordered":true} -->')
        ->toContain('<ol class="wp-block-list">')
        ->toContain('<!-- wp:html -->')
        ->toContain('<blockquote>')
        ->toContain('<hr>');
});

it('escapes raw html in markdown and blocks unsafe links', function () {
    $blocks = (new MarkdownToBlocks)->fromMarkdown("<script>alert(1)</script>\n\n[x](javascript:alert(1))");

    expect($blocks)->not->toContain('<script>')
        ->and($blocks)->not->toContain('href="javascript:');
});

it('keeps non-ascii characters intact', function () {
    expect((new MarkdownToBlocks)->fromMarkdown('Één café – “quote” €'))->toContain('Één café – “quote” €');
});

it('returns no blocks for empty input', function () {
    expect((new MarkdownToBlocks)->fromMarkdown(''))->toBe('')
        ->and((new MarkdownToBlocks)->fromPlainText(" \n"))->toBe('');
});
