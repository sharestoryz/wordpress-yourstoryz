<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use YourStoryz\PostType;

beforeEach(function () {
    Functions\when('__')->returnArg(1);
    Functions\when('sanitize_title')->returnArg();
    Functions\when('get_option')->alias(fn (string $key, mixed $default = false): mixed => $GLOBALS['yourstoryz_options'][$key] ?? $default);
    $GLOBALS['yourstoryz_options'] = [];
});

it('registers the post type on init', function () {
    Actions\expectAdded('init')->twice();

    (new PostType)->register();

    $this->addToAssertionCount(1);
});

it('uses the stored slug for the rewrite rules', function () {
    $GLOBALS['yourstoryz_options']['yourstoryz_slug'] = 'verhalen';
    $arguments = null;
    Functions\expect('register_post_type')->once()->andReturnUsing(function (string $name, array $args) use (&$arguments) {
        $arguments = $args;
    });
    Functions\expect('register_taxonomy_for_object_type')->once();

    (new PostType)->registerPostType();

    expect($arguments['rewrite'])->toBe(['slug' => 'verhalen']);
});

it('flushes the rewrite rules only when a flush is pending', function () {
    Functions\when('delete_option')->justReturn(true);
    $flushes = 0;
    Functions\expect('flush_rewrite_rules')->once()->andReturnUsing(function () use (&$flushes) {
        $flushes++;
    });

    (new PostType)->flushPendingRewrite();

    expect($flushes)->toBe(0);

    $GLOBALS['yourstoryz_options']['yourstoryz_flush_rewrite'] = '1';
    (new PostType)->flushPendingRewrite();

    expect($flushes)->toBe(1);
});

it('registers the story type with the expected arguments', function () {
    $arguments = null;
    Functions\expect('register_post_type')
        ->once()
        ->andReturnUsing(function (string $name, array $args) use (&$arguments) {
            $arguments = [$name, $args];
        });
    Functions\expect('register_taxonomy_for_object_type')->once();

    (new PostType)->registerPostType();

    [$name, $args] = $arguments;

    expect($name)->toBe('yourstoryz_story')
        ->and($args['public'])->toBeTrue()
        ->and($args['has_archive'])->toBeTrue()
        ->and($args['show_in_rest'])->toBeTrue()
        ->and($args['show_in_menu'])->toBe('yourstoryz')
        ->and($args['rewrite'])->toBe(['slug' => 'stories'])
        ->and($args['supports'])->toBe(['title', 'editor', 'excerpt', 'thumbnail'])
        ->and($args['labels']['name'])->toBe('Stories');
});

it('attaches post_tag to the story type', function () {
    $attached = [];
    Functions\expect('register_post_type')->once();
    Functions\expect('register_taxonomy_for_object_type')
        ->once()
        ->andReturnUsing(function (string $taxonomy, string $postType) use (&$attached) {
            $attached = [$taxonomy, $postType];
        });

    (new PostType)->registerPostType();

    expect($attached)->toBe(['post_tag', 'yourstoryz_story']);
});

it('registers the type before flushing rewrite rules on activation', function () {
    $calls = [];
    Functions\expect('register_post_type')->once()->andReturnUsing(function () use (&$calls) {
        $calls[] = 'register';
    });
    Functions\expect('register_taxonomy_for_object_type')->once();
    Functions\expect('flush_rewrite_rules')->once()->andReturnUsing(function () use (&$calls) {
        $calls[] = 'flush';
    });

    PostType::activate();

    expect($calls)->toBe(['register', 'flush']);
});

it('unregisters the type and flushes rewrite rules on deactivation', function () {
    $calls = [];
    Functions\expect('unregister_post_type')->once()->andReturnUsing(function (string $name) use (&$calls) {
        $calls[] = 'unregister:'.$name;
    });
    Functions\expect('flush_rewrite_rules')->once()->andReturnUsing(function () use (&$calls) {
        $calls[] = 'flush';
    });

    PostType::deactivate();

    expect($calls)->toBe(['unregister:yourstoryz_story', 'flush']);
});
