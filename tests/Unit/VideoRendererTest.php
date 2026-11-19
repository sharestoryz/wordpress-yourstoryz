<?php

use Brain\Monkey\Functions;
use YourStoryz\Frontend\VideoRenderer;

beforeEach(function () {
    Functions\when('esc_url')->alias(fn (string $url): string => str_replace(' ', '%20', $url));
    Functions\when('esc_attr')->alias(fn (string $value): string => htmlspecialchars($value, ENT_QUOTES));
});

it('renders a video with the expected attributes', function () {
    $html = (new VideoRenderer)->render('https://cdn.test/a.mp4', 'horizontal', 'https://cdn.test/p.jpg');

    expect($html)
        ->toContain('<video ')
        ->toContain(' controls preload="none" playsinline')
        ->toContain('poster="https://cdn.test/p.jpg"')
        ->toContain('src="https://cdn.test/a.mp4"');
});

it('uses 16:9 for horizontal and unknown orientation', function (?string $orientation) {
    $html = (new VideoRenderer)->render('https://cdn.test/a.mp4', $orientation);

    expect($html)->toContain('ys:aspect-video')->not->toContain('ys:aspect-9/16');
})->with(['horizontal', null, '']);

it('uses 9:16 and a capped width for vertical', function () {
    $html = (new VideoRenderer)->render('https://cdn.test/a.mp4', 'vertical');

    expect($html)->toContain('ys:aspect-9/16')->toContain('ys:max-w-sm!')->not->toContain('ys:aspect-video');
});

it('omits the poster when there is none', function () {
    expect((new VideoRenderer)->render('https://cdn.test/a.mp4', null))->not->toContain('poster');
});

it('renders nothing without a video url', function () {
    expect((new VideoRenderer)->render('', 'horizontal', 'https://cdn.test/p.jpg'))->toBe('');
});

it('renders nothing when the url is rejected by esc_url', function () {
    Functions\when('esc_url')->justReturn('');

    expect((new VideoRenderer)->render('javascript:alert(1)', null))->toBe('');
});

it('escapes the url and the poster', function () {
    $html = (new VideoRenderer)->render('https://cdn.test/a b.mp4?x="><script>', null, 'https://cdn.test/p q.jpg?y="onerror="x');

    expect($html)
        ->toContain('src="https://cdn.test/a%20b.mp4?x=&quot;&gt;&lt;script&gt;"')
        ->toContain('poster="https://cdn.test/p%20q.jpg?y=&quot;onerror=&quot;x"')
        ->not->toContain('<script>');
});
