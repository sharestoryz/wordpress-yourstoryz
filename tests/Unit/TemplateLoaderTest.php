<?php

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use YourStoryz\Frontend\TemplateLoader;

function yourstoryzLoader(): TemplateLoader
{
    return new TemplateLoader('/plugin/');
}

function yourstoryzContext(bool $blockTheme, bool $single, bool $archive): void
{
    $GLOBALS['yourstoryz_context'] = [$blockTheme, $single, $archive];

    Functions\expect('wp_is_block_theme')->andReturnUsing(fn (): bool => $GLOBALS['yourstoryz_context'][0]);
    Functions\expect('is_singular')->andReturnUsing(fn (): bool => $GLOBALS['yourstoryz_context'][1]);
    Functions\expect('is_post_type_archive')->andReturnUsing(fn (): bool => $GLOBALS['yourstoryz_context'][2]);
}

it('hooks into template_include', function () {
    Filters\expectAdded('template_include')->once();

    yourstoryzLoader()->register();
});

it('does not intervene for block themes', function () {
    yourstoryzContext(true, false, false);
    Functions\when('locate_template')->alias(fn (): string => throw new RuntimeException('locate_template called'));

    expect(yourstoryzLoader()->filter('/theme/index.php'))->toBe('/theme/index.php');
});

it('leaves other pages alone', function () {
    yourstoryzContext(false, false, false);
    Functions\when('locate_template')->alias(fn (): string => throw new RuntimeException('locate_template called'));

    expect(yourstoryzLoader()->filter('/theme/index.php'))->toBe('/theme/index.php');
});

it('prefers the theme override for a single story', function () {
    yourstoryzContext(false, true, false);
    Functions\when('locate_template')->alias(fn (array $templates): string => $templates === ['yourstoryz/single-story.php'] ? '/theme/yourstoryz/single-story.php' : 'wrong');

    expect(yourstoryzLoader()->filter('/theme/index.php'))->toBe('/theme/yourstoryz/single-story.php');
});

it('falls back to the plugin template for a single story', function () {
    yourstoryzContext(false, true, false);
    Functions\when('locate_template')->justReturn('');

    expect(yourstoryzLoader()->filter('/theme/index.php'))->toBe('/plugin/templates/single-story.php');
});

it('prefers the theme override for the archive', function () {
    yourstoryzContext(false, false, true);
    Functions\when('locate_template')->alias(fn (array $templates): string => $templates === ['yourstoryz/archive-story.php'] ? '/theme/yourstoryz/archive-story.php' : 'wrong');

    expect(yourstoryzLoader()->filter('/theme/index.php'))->toBe('/theme/yourstoryz/archive-story.php');
});

it('falls back to the plugin template for the archive', function () {
    yourstoryzContext(false, false, true);
    Functions\when('locate_template')->justReturn('');

    expect(yourstoryzLoader()->filter('/theme/index.php'))->toBe('/plugin/templates/archive-story.php');
});

it('loads parts with args, theme override first', function () {
    Functions\when('locate_template')->alias(fn (array $templates): string => $templates === ['yourstoryz/parts/story-card.php'] ? '' : 'wrong');
    $loaded = [];
    Functions\when('load_template')->alias(function (string $file, bool $once, array $args) use (&$loaded): void {
        $loaded = [$file, $once, $args];
    });

    yourstoryzLoader()->part('story-card', ['title' => 'A']);

    expect($loaded)->toBe(['/plugin/templates/parts/story-card.php', false, ['title' => 'A']]);
});
