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
            ? 'ys:mx-auto ys:block ys:aspect-9/16 ys:w-full ys:max-w-sm! ys:rounded-lg ys:bg-black ys:object-contain'
            : 'ys:block ys:aspect-video ys:w-full ys:rounded-xl ys:bg-black ys:object-contain';

        $poster = esc_url($posterUrl);
        $posterAttribute = $poster === '' ? '' : ' poster="'.esc_attr($poster).'"';

        $autoplay = $this->settings->autoplay();
        $video = '<video class="'.esc_attr($classes).'" controls preload="none" playsinline'.($autoplay ? ' autoplay muted' : '').$posterAttribute.' src="'.esc_attr($source).'"></video>';

        if ($autoplay) {
            return $video;
        }

        // video.js shows the play button and hides the native controls until the video plays.
        wp_enqueue_script(Assets::VIDEO_HANDLE);

        return '<div class="ys-video ys:relative" data-ys-video>'.$video
            .'<button type="button" class="ys-video__play" data-ys-play hidden aria-label="'.esc_attr__('Video afspelen', 'yourstoryz').'">'
            .'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13l10.5-6.5z" fill="currentColor"/></svg>'
            .'</button></div>';
    }
}
