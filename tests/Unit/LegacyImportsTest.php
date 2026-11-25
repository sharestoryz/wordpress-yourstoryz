<?php

use Brain\Monkey\Functions;
use YourStoryz\Import\LegacyImports;

beforeEach(function () {
    $this->store = [];
    $this->autoload = null;

    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('update_option')->alias(function ($key, $value, $autoload = null) {
        $this->store[$key] = $value;
        $this->autoload = $autoload;

        return true;
    });
});

it('is empty without a saved registry or with a corrupt one', function (mixed $saved) {
    $this->store['yourstoryz_legacy_imports'] = $saved;

    expect((new LegacyImports)->all())->toBe(['story_ids' => [], 'video_ids' => [], 'content_ids' => []])
        ->and((new LegacyImports)->contains(1, 2, 3))->toBeFalse();
})->with([[null], ['nope'], [['story_ids' => 'x', 'video_ids' => null]]]);

it('normalises ids to unique positive ints', function () {
    $this->store['yourstoryz_legacy_imports'] = ['story_ids' => ['5', 5, 0, -2, 'abc', 7.5, '9'], 'video_ids' => [3, 3]];

    expect((new LegacyImports)->all())->toBe(['story_ids' => [5, 9], 'video_ids' => [3], 'content_ids' => []]);
});

it('finds a story, video or content id', function (int $story, int $video, ?int $content, bool $expected) {
    $this->store['yourstoryz_legacy_imports'] = ['story_ids' => [10], 'video_ids' => [20], 'content_ids' => [30]];

    expect((new LegacyImports)->contains($story, $video, $content))->toBe($expected);
})->with([
    'story' => [10, 1, 1, true],
    'video' => [1, 20, 1, true],
    'content' => [1, 1, 30, true],
    'none' => [1, 1, 1, false],
    'no content id' => [1, 1, null, false],
]);

it('merges ids without duplicates and saves without autoload', function () {
    $registry = new LegacyImports;

    $registry->merge(['4', '5'], [1], [9]);
    $registry->merge([5, 6], [1, 2]);

    expect($this->store['yourstoryz_legacy_imports'])->toBe(['story_ids' => [4, 5, 6], 'video_ids' => [1, 2], 'content_ids' => [9]])
        ->and($this->autoload)->toBeFalse();
});

it('reads the option once per instance and sees its own merges', function () {
    $reads = 0;
    Functions\when('get_option')->alias(function ($key, $default = false) use (&$reads) {
        $reads++;

        return $this->store[$key] ?? $default;
    });
    $this->store['yourstoryz_legacy_imports'] = ['story_ids' => [5]];

    $legacyImports = new LegacyImports;

    expect($legacyImports->contains(5, 0, null))->toBeTrue()
        ->and($legacyImports->contains(6, 0, null))->toBeFalse()
        ->and($reads)->toBe(1);

    $legacyImports->merge([6]);

    expect($legacyImports->contains(6, 0, null))->toBeTrue();
});
