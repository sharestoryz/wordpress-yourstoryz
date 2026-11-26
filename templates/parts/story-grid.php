<?php
/**
 * Organism: responsive grid of story cards.
 *
 * @var array{stories: list<array<string, mixed>>, column_class: string, heading_level?: int, loader: TemplateLoader} $args  stories as built by StoryCard::fromPost(); loader (added by TemplateLoader::part) renders the cards; column_class holds the responsive column utilities, e.g. ys:sm:grid-cols-2 ys:md:grid-cols-3; heading_level (2 or 3) is passed to the cards.
 */

use YourStoryz\Frontend\TemplateLoader;

defined('ABSPATH') || exit;

?>
<div class="ys:grid ys:grid-cols-1 ys:gap-x-8 ys:gap-y-12 <?php echo esc_attr($args['column_class']); ?>">
    <?php foreach ($args['stories'] as $story) {
        $args['loader']->part('story-card', $story + ['heading_level' => $args['heading_level'] ?? 2]);
    } ?>
</div>
