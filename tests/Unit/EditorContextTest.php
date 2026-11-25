<?php

use YourStoryz\Frontend\EditorContext;

afterEach(function () {
    unset($GLOBALS['wp']);
});

it('detects the block renderer route', function (string $route, bool $expected) {
    $GLOBALS['wp'] = (object) ['query_vars' => ['rest_route' => $route]];

    expect(EditorContext::isBlockRenderer())->toBe($expected);
})->with([
    ['/wp/v2/block-renderer/yourstoryz/story', true],
    ['/wp/v2/pages/12', false],
    ['/wp/v2/yourstoryz_story/12', false],
    ['', false],
]);

it('is not the block renderer without a rest route', function () {
    $GLOBALS['wp'] = (object) ['query_vars' => []];

    expect(EditorContext::isBlockRenderer())->toBeFalse();
});

it('is not the block renderer without the wp global', function () {
    expect(EditorContext::isBlockRenderer())->toBeFalse();
});
