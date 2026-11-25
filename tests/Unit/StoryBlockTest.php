<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use YourStoryz\Frontend\StoryBlock;
use YourStoryz\Frontend\TemplateLoader;
use YourStoryz\Frontend\VideoRenderer;

function yourstoryzStoryBlock(bool $editing = false): StoryBlock
{
    $directory = dirname(__DIR__, 2).'/';

    return new StoryBlock(new TemplateLoader($directory), new VideoRenderer, $directory, $editing);
}

function yourstoryzStoryPost(int $id, array $fields = []): WP_Post
{
    $post = new WP_Post;
    $post->ID = $id;
    $post->post_type = 'yourstoryz_story';
    $post->post_status = 'publish';
    $post->post_title = 'Story '.$id;
    $post->post_content = '<p>Inhoud '.$id.'</p>';

    foreach ($fields as $field => $value) {
        $post->$field = $value;
    }

    return $post;
}

beforeEach(function () {
    $GLOBALS['yourstoryz_posts'] = [12 => yourstoryzStoryPost(12)];
    $GLOBALS['yourstoryz_story_calls'] = [];
    yourstoryzWpState();
    Functions\when('esc_html')->alias(fn (string $text): string => htmlspecialchars($text));
    Functions\when('esc_html__')->returnArg();
    Functions\when('get_option')->justReturn('0');
    Functions\when('locate_template')->justReturn('');
    Functions\when('get_post')->alias(fn (int $id): ?WP_Post => $GLOBALS['yourstoryz_posts'][$id] ?? null);
    Functions\when('get_the_title')->alias(fn (WP_Post $post): string => $post->post_title);
    Functions\when('get_the_tags')->justReturn(false);
    Functions\when('setup_postdata')->alias(function (WP_Post $post): void {
        $GLOBALS['yourstoryz_story_calls'][] = $post->ID;
    });
    Functions\when('get_block_wrapper_attributes')->justReturn('class="wp-block-yourstoryz-story"');
    Functions\when('load_template')->alias(function (string $file, bool $once, array $args): void {
        require $file;
    });
});

it('hooks the block registration on init', function () {
    Actions\expectAdded('init')->once();

    yourstoryzStoryBlock()->register();

    $this->addToAssertionCount(1);
});

it('registers the block from its directory with a render callback', function () {
    $GLOBALS['yourstoryz_registered'] = [];
    Functions\expect('register_block_type')->andReturnUsing(function (string $path, array $args): void {
        $GLOBALS['yourstoryz_registered'] = [$path, array_keys($args)];
    });

    yourstoryzStoryBlock()->registerBlock();

    expect($GLOBALS['yourstoryz_registered'][0])->toEndWith('/blocks/story')
        ->and($GLOBALS['yourstoryz_registered'][1])->toBe(['render_callback']);
});

it('renders the video, title and content of a published story', function () {
    $html = yourstoryzStoryBlock()->render(['storyId' => 12]);

    expect($html)->toStartWith('<div class="wp-block-yourstoryz-story"><article')
        ->toContain('<h2 class="ys:m-0">Story 12</h2>')
        ->toContain('<video ')
        ->toContain('<p>Inhoud 12</p>');
});

it('renders nothing for a draft story', function () {
    $GLOBALS['yourstoryz_posts'][12] = yourstoryzStoryPost(12, ['post_status' => 'draft']);

    expect(yourstoryzStoryBlock()->render(['storyId' => 12]))->toBe('');
});

it('renders nothing for an unknown id', function () {
    expect(yourstoryzStoryBlock()->render(['storyId' => 999]))->toBe('')
        ->and(yourstoryzStoryBlock()->render())->toBe('');
});

it('renders nothing for another post type', function () {
    $GLOBALS['yourstoryz_posts'][12] = yourstoryzStoryPost(12, ['post_type' => 'page']);

    expect(yourstoryzStoryBlock()->render(['storyId' => 12]))->toBe('');
});

it('escapes the title', function () {
    $GLOBALS['yourstoryz_posts'][12] = yourstoryzStoryPost(12, ['post_title' => '<script>alert(1)</script>']);

    expect(yourstoryzStoryBlock()->render(['storyId' => 12]))
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>');
});

it('shows a notice in the editor for an invalid story', function () {
    expect(yourstoryzStoryBlock(true)->render(['storyId' => 999]))
        ->toBe('<div class="wp-block-yourstoryz-story"><p>Deze story bestaat niet of is niet gepubliceerd.</p></div>');
});

it('does not render a story inside its own content', function () {
    $block = yourstoryzStoryBlock();
    Filters\expectApplied('the_content')->twice()->andReturnUsing(function (string $content) use ($block): string {
        return $content.$block->render(['storyId' => 12]);
    });

    $html = $block->render(['storyId' => 12]);

    expect(substr_count($html, '<article'))->toBe(1);
    expect($block->render(['storyId' => 12]))->toContain('<article');
});

it('sets up the story as the current post while filtering its content', function () {
    $GLOBALS['post'] = yourstoryzStoryPost(5);

    yourstoryzStoryBlock()->render(['storyId' => 12]);

    expect($GLOBALS['yourstoryz_story_calls'])->toBe([12, 5])
        ->and($GLOBALS['post']->ID)->toBe(5);
});
