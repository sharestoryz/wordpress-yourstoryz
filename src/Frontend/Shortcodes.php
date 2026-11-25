<?php

declare(strict_types=1);

namespace YourStoryz\Frontend;

final class Shortcodes
{
    public function register(): void
    {
        add_action('init', [$this, 'registerShortcodes']);
    }

    public function registerShortcodes(): void
    {
        add_shortcode('yourstoryz_stories', [$this, 'stories']);
        add_shortcode('yourstoryz_story', [$this, 'story']);
    }

    /**
     * @param  array<string, mixed>|string  $attributes
     */
    public function stories(array|string $attributes = []): string
    {
        $atts = shortcode_atts([
            'count' => null,
            'columns' => null,
            'tag' => null,
            'archive_link' => null,
        ], (array) $attributes, 'yourstoryz_stories');

        $blockAttributes = [];

        if (isset($atts['count'])) {
            $blockAttributes['count'] = (int) $atts['count'];
        }

        if (isset($atts['columns'])) {
            $blockAttributes['columns'] = (int) $atts['columns'];
        }

        if (isset($atts['tag'])) {
            $blockAttributes['tag'] = (string) $atts['tag'];
        }

        if (isset($atts['archive_link'])) {
            $blockAttributes['showArchiveLink'] = filter_var(
                $atts['archive_link'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            ) ?? true;
        }

        return $this->renderBlock('yourstoryz/stories', $blockAttributes);
    }

    /**
     * @param  array<string, mixed>|string  $attributes
     */
    public function story(array|string $attributes = []): string
    {
        $atts = shortcode_atts(['id' => 0], (array) $attributes, 'yourstoryz_story');
        $storyId = absint($atts['id']);

        if ($storyId === 0) {
            return '';
        }

        return $this->renderBlock('yourstoryz/story', ['storyId' => $storyId]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function renderBlock(string $name, array $attributes): string
    {
        return render_block([
            'blockName' => $name,
            'attrs' => $attributes,
            'innerBlocks' => [],
            'innerHTML' => '',
            'innerContent' => [],
        ]);
    }
}
