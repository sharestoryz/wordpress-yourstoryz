<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use YourStoryz\Frontend\StoriesBlock;
use YourStoryz\Frontend\TemplateLoader;

function yourstoryzStoriesBlock(): StoriesBlock
{
    $directory = dirname(__DIR__, 2).'/';

    return new StoriesBlock(new TemplateLoader($directory), $directory);
}

function yourstoryzStory(int $id, string $title): WP_Post
{
    $post = new WP_Post;
    $post->ID = $id;
    $post->post_title = $title;

    return $post;
}

beforeEach(function () {
    WP_Query::$lastArgs = [];
    WP_Query::$stories = [yourstoryzStory(1, 'Eerste story')];
    yourstoryzWpState(['thumbnail' => 'https://cdn.test/p.jpg']);
    Functions\when('esc_html')->alias(fn (string $text): string => htmlspecialchars($text));
    Functions\when('esc_html__')->returnArg();
    Functions\when('sanitize_title')->alias(fn (string $title): string => strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-')));
    $GLOBALS['yourstoryz_styles'] = [];
    Functions\when('wp_enqueue_style')->alias(function (string $handle): void {
        $GLOBALS['yourstoryz_styles'][] = $handle;
    });
    Functions\when('locate_template')->justReturn('');
    Functions\when('get_permalink')->alias(fn (WP_Post $post): string => 'https://example.test/story/'.$post->ID);
    Functions\when('get_the_title')->alias(fn (WP_Post $post): string => $post->post_title);
    Functions\when('get_post_type_archive_link')->justReturn('https://example.test/stories/');
    Functions\when('get_block_wrapper_attributes')->justReturn('class="wp-block-yourstoryz-stories"');
    Functions\when('load_template')->alias(function (string $file, bool $once, array $args): void {
        require $file;
    });
});

it('hooks the block registration on init', function () {
    Actions\expectAdded('init')->once();

    yourstoryzStoriesBlock()->register();

    $this->addToAssertionCount(1);
});

it('registers the block from its directory with a render callback', function () {
    $registrations = yourstoryzCaptureBlockRegistrations();

    yourstoryzStoriesBlock()->registerBlock();

    expect($registrations)->toHaveCount(1)
        ->and($registrations[0][0])->toEndWith('/blocks/stories')
        ->and($registrations[0][1])->toBe(['render_callback']);
});

it('queries the newest published stories with the default count', function () {
    yourstoryzStoriesBlock()->render();

    expect(WP_Query::$lastArgs)->toMatchArray([
        'post_type' => 'yourstoryz_story',
        'post_status' => 'publish',
        'posts_per_page' => 6,
    ]);
});

it('filters by tag slug only when a tag is set', function () {
    yourstoryzStoriesBlock()->render(['tag' => 'NL Nieuws']);

    expect(WP_Query::$lastArgs['tag'])->toBe('nl-nieuws');

    yourstoryzStoriesBlock()->render(['tag' => '']);

    expect(WP_Query::$lastArgs)->not->toHaveKey('tag');
});

it('clamps the count between 1 and 24', function (int $given, int $expected) {
    yourstoryzStoriesBlock()->render(['count' => $given]);

    expect(WP_Query::$lastArgs['posts_per_page'])->toBe($expected);
})->with([[0, 1], [-5, 1], [10, 10], [99, 24]]);

it('clamps the columns between 1 and 4', function (int $given, string $expected) {
    expect(yourstoryzStoriesBlock()->render(['columns' => $given]))
        ->toContain('ys:grid-cols-1 ys:gap-x-8 ys:gap-y-12 '.$expected);
})->with([
    [0, 'ys:md:grid-cols-1'],
    [2, 'ys:sm:grid-cols-2 ys:md:grid-cols-2'],
    [4, 'ys:sm:grid-cols-2 ys:md:grid-cols-4'],
    [9, 'ys:sm:grid-cols-2 ys:md:grid-cols-4'],
]);

it('does not add a small-screen column class for a single column', function () {
    expect(yourstoryzStoriesBlock()->render(['columns' => 1]))->not->toContain('ys:sm:grid-cols-2');
});

it('enqueues the stylesheet only when it outputs stories', function () {
    yourstoryzStoriesBlock()->render();

    expect($GLOBALS['yourstoryz_styles'])->toBe(['yourstoryz-frontend']);

    $GLOBALS['yourstoryz_styles'] = [];
    WP_Query::$stories = [];
    yourstoryzStoriesBlock()->render();

    expect($GLOBALS['yourstoryz_styles'])->toBe([]);
});

it('renders a card for each story inside the wrapper', function () {
    WP_Query::$stories = [yourstoryzStory(1, 'Eerste'), yourstoryzStory(2, 'Tweede')];

    $html = yourstoryzStoriesBlock()->render();

    expect($html)->toStartWith('<div class="wp-block-yourstoryz-stories">')
        ->and(substr_count($html, '<article'))->toBe(2)
        ->and($html)->toContain('href="https://example.test/story/2"')
        ->toContain('src="https://cdn.test/p.jpg"');
});

it('renders an empty text without stories', function () {
    WP_Query::$stories = [];

    expect(yourstoryzStoriesBlock()->render())
        ->toContain('<p>Er zijn nog geen stories.</p>')
        ->not->toContain('<article')
        ->not->toContain('ys:grid ');
});

it('escapes story titles', function () {
    WP_Query::$stories = [yourstoryzStory(1, '<script>alert(1)</script> & co')];

    expect(yourstoryzStoriesBlock()->render())
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt; &amp; co')
        ->not->toContain('<script>');
});

it('links to the archive when enabled', function () {
    expect(yourstoryzStoriesBlock()->render())->toContain('<a href="https://example.test/stories/">');
});

it('omits the archive link when disabled or unavailable', function () {
    expect(yourstoryzStoriesBlock()->render(['showArchiveLink' => false]))->not->toContain('https://example.test/stories/');

    Functions\when('get_post_type_archive_link')->justReturn(false);

    expect(yourstoryzStoriesBlock()->render())->not->toContain('<a href="https://example.test/stories/');
});
