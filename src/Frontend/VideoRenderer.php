<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

use YourStoryz\Settings;
use YourStoryz\StoryMeta;

final class VideoRenderer
{
    public function __construct(
        private readonly Settings $settings = new Settings,
    ) {}

    public function renderPost(int $postId): string
    {
        return $this->render(
            (string) get_post_meta($postId, StoryMeta::VIDEO_URL, true),
            (string) get_post_meta($postId, StoryMeta::ORIENTATION, true),
            (string) get_the_post_thumbnail_url($postId, 'large'),
        );
    }

    public function render(string $videoUrl, ?string $orientation, string $posterUrl = ''): string
    {
        $source = esc_url($videoUrl);

        if ($source === '') {
            return '';
        }

        $classes = $orientation === 'vertical'
            ? 'ys:mx-auto ys:block ys:aspect-9/16 ys:w-full ys:max-w-sm! ys:bg-black ys:object-contain'
            : 'ys:block ys:aspect-video ys:w-full ys:bg-black ys:object-contain';

        $poster = esc_url($posterUrl);
        $posterAttribute = $poster === '' ? '' : ' poster="'.esc_attr($poster).'"';

        $autoplayAttributes = $this->settings->autoplay() ? ' autoplay muted' : '';

        return '<video class="'.esc_attr($classes).'" controls preload="none" playsinline'.$autoplayAttributes.$posterAttribute.' src="'.esc_attr($source).'"></video>';
    }
}
