<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use YourStoryz\Frontend\Assets;

function yourstoryzAssets(): Assets
{
    return new Assets(
        sys_get_temp_dir().'/yourstoryz-assets-test/',
        sys_get_temp_dir().'/yourstoryz-assets-test/YourStoryz.php',
        '9.9.9',
    );
}

function yourstoryzStylesheetPath(): string
{
    return sys_get_temp_dir().'/yourstoryz-assets-test/assets/build/frontend.css';
}

function yourstoryzSetBuild(bool $exists): void
{
    $path = yourstoryzStylesheetPath();

    if ($exists && ! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    if ($exists) {
        file_put_contents($path, 'a{}');
    } elseif (file_exists($path)) {
        unlink($path);
    }
}

beforeEach(function () {
    $GLOBALS['yourstoryz_styles'] = [];
    Functions\when('wp_enqueue_style')->alias(function (string $handle): void {
        $GLOBALS['yourstoryz_styles'][] = $handle;
    });
});

afterEach(function () {
    yourstoryzSetBuild(false);
});

it('hooks into wp_enqueue_scripts', function () {
    Actions\expectAdded('wp_enqueue_scripts')->once();

    yourstoryzAssets()->register();
});

it('enqueues the stylesheet on a single story', function () {
    yourstoryzWpState(['singular' => true]);

    yourstoryzAssets()->enqueue();

    expect($GLOBALS['yourstoryz_styles'])->toBe(['yourstoryz-frontend']);
});

it('enqueues the stylesheet on the story archive', function () {
    yourstoryzWpState(['archive' => true]);

    yourstoryzAssets()->enqueue();

    expect($GLOBALS['yourstoryz_styles'])->toBe(['yourstoryz-frontend']);
});

it('does not enqueue on other pages', function () {
    yourstoryzWpState();

    yourstoryzAssets()->enqueue();

    expect($GLOBALS['yourstoryz_styles'])->toBe([]);
});

it('registers the stylesheet handle on init', function () {
    yourstoryzSetBuild(true);
    Actions\expectAdded('init')->once();
    Functions\expect('plugins_url')->once()->andReturn('https://example.test/frontend.css');
    Functions\expect('wp_register_style')->once()->with('yourstoryz-frontend', 'https://example.test/frontend.css', [], '9.9.9');

    $assets = yourstoryzAssets();
    $assets->register();
    $assets->registerStyle();

    $this->addToAssertionCount(1);
});

it('does not register the stylesheet when the build file is missing', function () {
    yourstoryzSetBuild(false);
    Functions\expect('wp_register_style')->never();

    yourstoryzAssets()->registerStyle();

    $this->addToAssertionCount(1);
});
