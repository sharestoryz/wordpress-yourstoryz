<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use YourStoryz\Frontend\BlockTemplates;
use YourStoryz\Frontend\VideoRenderer;

function yourstoryzBlockTemplates(bool $supported): BlockTemplates
{
    return new BlockTemplates(new VideoRenderer, dirname(__DIR__, 2).'/', $supported);
}

function yourstoryzBlockContext(bool $blockTheme, bool $single, bool $loop, bool $main): void
{
    yourstoryzWpState([
        'block_theme' => $blockTheme,
        'singular' => $single,
        'in_loop' => $loop,
        'main_query' => $main,
    ]);
}

it('hooks the registration and the content fallback', function () {
    yourstoryzWpState();
    Actions\expectAdded('init')->once();
    Filters\expectAdded('the_content')->once();

    yourstoryzBlockTemplates(true)->register();
});

it('registers both templates on a block theme with support', function () {
    yourstoryzBlockContext(true, false, false, false);

    yourstoryzBlockTemplates(true)->registerTemplates();

    expect(array_keys($GLOBALS['yourstoryz_wp']['registered']))->toBe([
        'yourstoryz//single-yourstoryz_story',
        'yourstoryz//archive-yourstoryz_story',
    ]);
    expect($GLOBALS['yourstoryz_wp']['registered']['yourstoryz//single-yourstoryz_story']['content'])->toContain('wp:yourstoryz/video');
    expect($GLOBALS['yourstoryz_wp']['registered']['yourstoryz//archive-yourstoryz_story']['content'])->toContain('"inherit":true');
});

it('does not register templates on a classic theme', function () {
    yourstoryzBlockContext(false, false, false, false);

    yourstoryzBlockTemplates(true)->registerTemplates();

    expect($GLOBALS['yourstoryz_wp']['registered'])->toBe([]);
});

it('does not register templates without register_block_template', function () {
    yourstoryzBlockContext(true, false, false, false);

    yourstoryzBlockTemplates(false)->registerTemplates();

    expect($GLOBALS['yourstoryz_wp']['registered'])->toBe([]);
});

it('prepends the video on a singular story in a block theme without template support', function () {
    yourstoryzBlockContext(true, true, true, true);

    expect(yourstoryzBlockTemplates(false)->prependVideo('<p>Body</p>'))
        ->toStartWith('<video ')
        ->toEndWith('<p>Body</p>');
});

it('does not prepend when the block template is active', function () {
    yourstoryzBlockContext(true, true, true, true);

    expect(yourstoryzBlockTemplates(true)->prependVideo('<p>Body</p>'))->toBe('<p>Body</p>');
});

it('does not prepend outside a block theme', function () {
    yourstoryzBlockContext(false, true, true, true);

    expect(yourstoryzBlockTemplates(false)->prependVideo('<p>Body</p>'))->toBe('<p>Body</p>');
});

it('does not prepend off singular stories, outside the loop or in secondary queries', function (bool $single, bool $loop, bool $main) {
    yourstoryzBlockContext(true, $single, $loop, $main);

    expect(yourstoryzBlockTemplates(false)->prependVideo('<p>Body</p>'))->toBe('<p>Body</p>');
})->with([
    'not a story' => [false, true, true],
    'outside the loop' => [true, false, true],
    'secondary query' => [true, true, false],
]);
