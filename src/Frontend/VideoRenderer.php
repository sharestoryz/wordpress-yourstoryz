<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

final class VideoRenderer
{
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

        return '<video class="'.esc_attr($classes).'" controls preload="none" playsinline'.$posterAttribute.' src="'.esc_attr($source).'"></video>';
    }
}
