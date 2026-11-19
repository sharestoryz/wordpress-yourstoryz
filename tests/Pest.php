<?php

use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach(function () {
    Monkey\setUp();
});

afterEach(function () {
    Monkey\tearDown();
});

/**
 * Brain Monkey keeps the first closure given to Functions\expect() for the rest of the run,
 * so the stubs read their answers from this shared state instead of capturing test values.
 * A test must therefore never call Functions\expect()/when() for a function this helper stubs: call this helper instead.
 *
 * @param  array<string, mixed>  $state
 */
function yourstoryzWpState(array $state = []): void
{
    $GLOBALS['yourstoryz_wp'] = $state + [
        'block_theme' => true,
        'singular' => false,
        'archive' => false,
        'in_loop' => false,
        'main_query' => false,
        'post_id' => 7,
        'meta' => ['_ysz_video_url' => 'https://cdn.test/a.mp4', '_ysz_orientation' => 'horizontal'],
        'thumbnail' => '',
        'registered' => [],
    ];

    Functions\expect('wp_is_block_theme')->andReturnUsing(fn (): bool => $GLOBALS['yourstoryz_wp']['block_theme']);
    Functions\expect('is_singular')->andReturnUsing(fn (string $postType = ''): bool => $GLOBALS['yourstoryz_wp']['singular'] && in_array($postType, ['', 'yourstoryz_story'], true));
    Functions\expect('is_post_type_archive')->andReturnUsing(fn (string $postType = ''): bool => $GLOBALS['yourstoryz_wp']['archive'] && in_array($postType, ['', 'yourstoryz_story'], true));
    Functions\expect('in_the_loop')->andReturnUsing(fn (): bool => $GLOBALS['yourstoryz_wp']['in_loop']);
    Functions\expect('is_main_query')->andReturnUsing(fn (): bool => $GLOBALS['yourstoryz_wp']['main_query']);
    Functions\expect('get_the_ID')->andReturnUsing(fn (): int|false => $GLOBALS['yourstoryz_wp']['post_id']);
    Functions\when('get_post_meta')->alias(fn (int $id, string $key): string => $GLOBALS['yourstoryz_wp']['meta'][$key] ?? '');
    Functions\expect('get_the_post_thumbnail_url')->andReturnUsing(fn (): string => $GLOBALS['yourstoryz_wp']['thumbnail']);
    Functions\expect('register_block_template')->andReturnUsing(function (string $name, array $args): void {
        $GLOBALS['yourstoryz_wp']['registered'][$name] = $args;
    });
    Functions\when('__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('esc_attr')->returnArg();
}
