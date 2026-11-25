<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use YourStoryz\Frontend\Shortcodes;

beforeEach(function () {
    $GLOBALS['yourstoryz_rendered'] = [];
    Functions\when('shortcode_atts')->alias(fn (array $defaults, array $atts): array => array_merge($defaults, array_intersect_key($atts, $defaults)));
    Functions\when('absint')->alias(fn (mixed $value): int => abs((int) $value));
    Functions\when('sanitize_title')->alias(fn (string $title): string => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-')));
    Functions\when('render_block')->alias(function (array $block): string {
        $GLOBALS['yourstoryz_rendered'][] = $block;

        return '<rendered/>';
    });
});

function yourstoryzRenderedBlock(): array
{
    return $GLOBALS['yourstoryz_rendered'][0];
}

it('hooks the shortcode registration on init', function () {
    Actions\expectAdded('init')->once();

    (new Shortcodes)->register();

    $this->addToAssertionCount(1);
});

it('adds both shortcodes', function () {
    Functions\expect('add_shortcode')->twice();

    (new Shortcodes)->registerShortcodes();

    $this->addToAssertionCount(1);
});

it('renders the stories block with the block defaults', function () {
    expect((new Shortcodes)->stories([]))->toBe('<rendered/>');

    expect(yourstoryzRenderedBlock())->toBe([
        'blockName' => 'yourstoryz/stories',
        'attrs' => ['count' => 6, 'columns' => 3, 'tag' => '', 'showArchiveLink' => true],
        'innerBlocks' => [],
        'innerHTML' => '',
        'innerContent' => [],
    ]);
});

it('maps and sanitizes the stories attributes', function () {
    (new Shortcodes)->stories(['count' => '4', 'columns' => '2', 'tag' => 'NL Nieuws', 'archive_link' => '0']);

    expect(yourstoryzRenderedBlock()['attrs'])->toBe(['count' => 4, 'columns' => 2, 'tag' => 'nl-nieuws', 'showArchiveLink' => false]);
});

it('passes out of range numbers to the block for clamping', function () {
    (new Shortcodes)->stories(['count' => '99', 'columns' => '-2']);

    expect(yourstoryzRenderedBlock()['attrs'])->toMatchArray(['count' => 99, 'columns' => -2]);
});

it('parses the archive_link boolean', function (string $value, bool $expected) {
    (new Shortcodes)->stories(['archive_link' => $value]);

    expect(yourstoryzRenderedBlock()['attrs']['showArchiveLink'])->toBe($expected);
})->with([['1', true], ['0', false], ['true', true], ['false', false], ['yes', true], ['no', false], ['nonsense', true]]);

it('treats an attribute-less shortcode string as defaults', function () {
    (new Shortcodes)->stories('');

    expect(yourstoryzRenderedBlock()['attrs']['count'])->toBe(6);
});

it('renders the story block with the story id', function () {
    expect((new Shortcodes)->story(['id' => '123']))->toBe('<rendered/>');

    expect(yourstoryzRenderedBlock())->toMatchArray([
        'blockName' => 'yourstoryz/story',
        'attrs' => ['storyId' => 123],
    ]);
});

it('returns an empty string without a valid story id', function (mixed $id) {
    expect((new Shortcodes)->story(['id' => $id]))->toBe('')
        ->and($GLOBALS['yourstoryz_rendered'])->toBe([]);
})->with(['0', 'abc', '']);
