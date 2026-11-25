<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use YourStoryz\Frontend\VideoBlock;
use YourStoryz\Frontend\VideoRenderer;

if (! class_exists('WP_Block')) {
    class WP_Block
    {
        /** @var array<string, mixed> */
        public array $context = [];
    }
}

function yourstoryzVideoBlock(bool $editing = false): VideoBlock
{
    return new VideoBlock(new VideoRenderer, '/plugin/', $editing);
}

function yourstoryzBlockWithContext(array $context): WP_Block
{
    $block = new WP_Block;
    $block->context = $context;

    return $block;
}

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('get_option')->justReturn('0');
    yourstoryzWpState([
        'meta' => ['_ysz_video_url' => 'https://cdn.test/a.mp4', '_ysz_orientation' => 'vertical'],
        'thumbnail' => 'https://cdn.test/p.jpg',
    ]);
});

it('hooks the block registration on init', function () {
    Actions\expectAdded('init')->once();

    yourstoryzVideoBlock()->register();

    $this->addToAssertionCount(1);
});

it('registers the block from its directory with a render callback', function () {
    $registrations = yourstoryzCaptureBlockRegistrations();

    yourstoryzVideoBlock()->registerBlock();

    expect($registrations->getArrayCopy())->toBe([['/plugin/blocks/video', ['render_callback']]]);
});

it('renders the video of the post in the block context', function () {
    $html = yourstoryzVideoBlock()->render([], '', yourstoryzBlockWithContext(['postId' => 12]));

    expect($html)->toContain('<video ')->toContain('ys:aspect-9/16')->toContain('poster="https://cdn.test/p.jpg"');
});

it('renders nothing on the front end without a post', function () {
    yourstoryzWpState(['post_id' => false]);

    expect(yourstoryzVideoBlock()->render([], '', yourstoryzBlockWithContext([])))->toBe('');
});

it('renders nothing on the front end without a video url', function () {
    yourstoryzWpState(['meta' => []]);

    expect(yourstoryzVideoBlock()->render([], '', yourstoryzBlockWithContext(['postId' => 12])))->toBe('');
});

it('renders a placeholder in the editor without a post', function () {
    yourstoryzWpState(['post_id' => false]);

    expect(yourstoryzVideoBlock(true)->render([], '', yourstoryzBlockWithContext([])))
        ->toContain('aspect-ratio:16/9')
        ->toContain('Story-video')
        ->not->toContain('<video');
});

it('renders a placeholder in the editor without a video url', function () {
    yourstoryzWpState(['meta' => []]);

    expect(yourstoryzVideoBlock(true)->render([], '', yourstoryzBlockWithContext(['postId' => 12])))->toContain('Story-video');
});

it('renders the real video in the editor when there is one', function () {
    expect(yourstoryzVideoBlock(true)->render([], '', yourstoryzBlockWithContext(['postId' => 12])))
        ->toContain('<video ')
        ->not->toContain('Story-video');
});

it('follows the autoplay setting', function () {
    Functions\when('get_option')->justReturn('0');

    expect(yourstoryzVideoBlock()->render([], '', yourstoryzBlockWithContext(['postId' => 12])))
        ->not->toContain('autoplay')
        ->not->toContain('muted');

    Functions\when('get_option')->justReturn('1');

    expect(yourstoryzVideoBlock()->render([], '', yourstoryzBlockWithContext(['postId' => 12])))
        ->toContain('autoplay')
        ->toContain('muted')
        ->toContain('playsinline');
});
