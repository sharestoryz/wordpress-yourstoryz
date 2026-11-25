<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use YourStoryz\Import\DeletedStories;

function deletedPost(string $type): WP_Post
{
    $post = new WP_Post;
    $post->post_type = $type;

    return $post;
}

beforeEach(function () {
    $this->store = [];
    $this->meta = ['_ysz_source_id' => '7', '_ysz_lang' => 'nl'];
    $this->autoload = null;

    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->store[$key] ?? $default);
    Functions\when('update_option')->alias(function ($key, $value, $autoload = null) {
        $this->store[$key] = $value;
        $this->autoload = $autoload;

        return true;
    });
    Functions\when('get_post_meta')->alias(fn ($id, $key, $single) => $this->meta[$key] ?? '');
});

it('hooks before_delete_post with two arguments', function () {
    Actions\expectAdded('before_delete_post')->once();

    (new DeletedStories)->register();
});

it('records the source and language key of a deleted story without autoload', function () {
    (new DeletedStories)->record(5, deletedPost('yourstoryz_story'));

    expect($this->store['yourstoryz_deleted_stories'])->toBe(['7|nl'])
        ->and($this->autoload)->toBeFalse();
});

it('keeps the list unique', function () {
    $deleted = new DeletedStories;

    $deleted->record(5, deletedPost('yourstoryz_story'));
    $deleted->record(6, deletedPost('yourstoryz_story'));
    $this->meta['_ysz_lang'] = 'en';
    $deleted->record(7, deletedPost('yourstoryz_story'));

    expect($this->store['yourstoryz_deleted_stories'])->toBe(['7|nl', '7|en']);
});

it('ignores other post types', function () {
    (new DeletedStories)->record(5, deletedPost('post'));

    expect($this->store)->toBe([]);
});

it('ignores story posts without the source meta', function (array $meta) {
    $this->meta = $meta;

    (new DeletedStories)->record(5, deletedPost('yourstoryz_story'));

    expect($this->store)->toBe([]);
})->with([
    'no meta' => [[]],
    'no language' => [['_ysz_source_id' => '7']],
    'no source id' => [['_ysz_lang' => 'nl']],
]);

it('returns an empty list for a malformed option', function () {
    $this->store['yourstoryz_deleted_stories'] = 'oops';

    expect((new DeletedStories)->keys())->toBe([]);
});
