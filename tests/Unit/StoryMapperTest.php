<?php

use Brain\Monkey\Functions;
use YourStoryz\Api\Gateway;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Api\StoryMapper;
use YourStoryz\Settings;
use YourStoryz\Source;
use YourStoryz\StoryCatalog;
use YourStoryz\Vendor\Saloon\Http\Faking\MockClient;
use YourStoryz\Vendor\Saloon\Http\Faking\MockResponse;
use YourStoryz\Vendor\YourStoryz\PhpSdk\Requests\Companies\GetStories as CompanyStories;
use YourStoryz\Vendor\YourStoryz\PhpSdk\Requests\Departments\GetStories as DepartmentStories;
use YourStoryz\Vendor\YourStoryz\PhpSdk\YourStoryz;

function storyFixtures(): array
{
    return json_decode(file_get_contents(__DIR__.'/../Fixtures/stories.json'), true);
}

function storyFixture(int $id): array
{
    foreach (storyFixtures() as $story) {
        if ($story['id'] === $id) {
            return $story;
        }
    } throw new RuntimeException("Fixture {$id} missing");
}

it('maps a video story with news and social contents', function () {
    $story = (new StoryMapper)->map(storyFixture(1313));

    expect($story->id)->toBe(1313)
        ->and($story->author)->toBe('Voorbeeldauteur')
        ->and($story->video->orientation)->toBe('horizontal')
        ->and($story->video->contents)->toHaveCount(4)
        ->and($story->video->contents[0]->lang)->toBe('en')
        ->and($story->video->contents[0]->type)->toBe('social')
        ->and($story->video->contents[0]->content)->toBeString()
        ->and($story->video->contents[0]->hashtags)->toBeArray();
});

it('maps a video without contents', function () {
    expect((new StoryMapper)->map(storyFixture(1023))->video->contents)->toBe([]);
});

it('keeps a null orientation null', function () {
    expect((new StoryMapper)->map(storyFixture(1697))->video->orientation)->toBeNull();
});

it('skips media stories', function () {
    $media = array_values(array_filter(storyFixtures(), fn (array $story): bool => $story['video'] === null))[0];

    expect((new StoryMapper)->map($media))->toBeNull();
});

it('tolerates a missing author', function () {
    $story = storyFixture(1697);
    unset($story['author']);

    expect((new StoryMapper)->map($story)->author)->toBeNull()
        ->and((new StoryMapper)->map(['id' => 1, 'video' => ['id' => 2]])->video->contents)->toBe([]);
});

it('counts video and media stories in the fixture', function () {
    $mapped = array_map((new StoryMapper)->map(...), storyFixtures());

    expect(array_filter($mapped))->toHaveCount(3);
});

it('uses the company or department endpoint', function (Source $source, string $request) {
    $mockClient = new MockClient([$request => MockResponse::make(storyFixtures())]);
    $gateway = new Gateway('token', fn (string $token) => (new YourStoryz($token))->withMockClient($mockClient));

    expect($gateway->stories($source))->toHaveCount(count(storyFixtures()));
    $mockClient->assertSent($request);
})->with([
    'company' => [new Source(116), CompanyStories::class],
    'department' => [new Source(116, 5), DepartmentStories::class],
]);

it('caches the mapped stories in a transient', function () {
    $transients = [];
    $requests = new ArrayObject(['count' => 0]);
    Functions\when('get_option')->alias(fn ($key, $default = false) => $key === 'yourstoryz_token' ? 'secret' : $default);
    Functions\when('get_transient')->alias(function ($key) use (&$transients) {
        return $transients[$key] ?? false;
    });
    Functions\when('set_transient')->alias(function ($key, $value) use (&$transients) {
        $transients[$key] = $value;
    });
    Functions\when('delete_transient')->alias(function ($key) use (&$transients) {
        unset($transients[$key]);
    });

    $catalog = new StoryCatalog(new GatewayProvider(new Settings, fn (string $token) => new Gateway($token, function (string $token) use ($requests) {
        $requests['count']++;

        return (new YourStoryz($token))->withMockClient(new MockClient([CompanyStories::class => MockResponse::make(storyFixtures())]));
    })));

    $first = $catalog->load(new Source(116));
    $catalog->load(new Source(116));

    expect($requests['count'])->toBe(1)
        ->and($first['skipped'])->toBe(2)
        ->and($first['stories'])->toHaveCount(3);

    $catalog->refresh(new Source(116));
    $catalog->load(new Source(116));

    expect($requests['count'])->toBe(2);
});

it('caches plain arrays and ignores rows written by an older cache version', function () {
    $transients = ['yourstoryz_stories_'.substr(md5('secret'), 0, 8).'_116_0' => ['stories' => ['broken'], 'skipped' => 0]];
    Functions\when('get_option')->alias(fn ($key, $default = false) => $key === 'yourstoryz_token' ? 'secret' : $default);
    Functions\when('get_transient')->alias(fn ($key) => $transients[$key] ?? false);
    Functions\when('set_transient')->alias(function ($key, $value) use (&$transients) {
        $transients[$key] = $value;
    });

    $catalog = new StoryCatalog(new GatewayProvider(new Settings, fn (string $token) => new Gateway($token, fn (string $token) => (new YourStoryz($token))->withMockClient(new MockClient([CompanyStories::class => MockResponse::make(storyFixtures())])))));

    $result = $catalog->load(new Source(116));
    $cached = $transients['yourstoryz_stories_v2_'.substr(md5('secret'), 0, 8).'_116_0'];

    expect($result['stories'])->toHaveCount(3)
        ->and($cached)->toBe(storyFixtures())
        ->and(serialize($cached))->not->toContain('O:');
});
