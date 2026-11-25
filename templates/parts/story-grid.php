<?php
/**
 * Organism: responsive grid of story cards.
 *
 * @var array{stories: list<array{url: string, title: string, thumbnail_url: string}>, column_class: string, loader: TemplateLoader} $args  loader (added by TemplateLoader::part) renders the cards; column_class holds the responsive column utilities, e.g. ys:sm:grid-cols-2 ys:md:grid-cols-3.
 */

use YourStoryz\Frontend\TemplateLoader;

defined('ABSPATH') || exit;

?>
<div class="ys:grid ys:grid-cols-1 ys:gap-6 <?php echo esc_attr($args['column_class']); ?>">
    <?php foreach ($args['stories'] as $story) {
        $args['loader']->part('story-card', $story);
    } ?>
</div>
