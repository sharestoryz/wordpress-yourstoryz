<?php
/**
 * Organism: the story overview with its title, the newest story large on page 1, the grid and pagination.
 *
 * @var array{title: string, count_label: string, feature: array<string, mixed>|null, stories: list<array<string, mixed>>, pagination: list<string>, loader: TemplateLoader} $args  Built by StoryPage::archive(); feature is null after page 1.
 */

use YourStoryz\Frontend\TemplateLoader;

defined('ABSPATH') || exit;
?>
<header class="ys-rule-bottom ys:mb-14 ys:flex ys:flex-wrap ys:items-end ys:justify-between ys:gap-4 ys:pb-8">
    <div class="ys:flex ys:flex-col ys:gap-3">
        <p class="ys:m-0 ys:text-sm ys:font-bold ys:uppercase ys:tracking-widest ys:text-(--ys-accent-2)"><?php echo esc_html__('Video-stories', 'yourstoryz'); ?></p>
        <h1 class="ys:m-0 ys:text-5xl ys:leading-none ys:font-extrabold ys:tracking-tight ys:md:text-6xl"><?php echo esc_html($args['title']); ?></h1>
    </div>
    <p class="ys:m-0 ys:text-sm ys:text-(--ys-muted)"><?php echo esc_html($args['count_label']); ?></p>
</header>
<?php if ($args['feature'] === null && $args['stories'] === []) { ?>
    <p><?php echo esc_html__('Geen stories gevonden.', 'yourstoryz'); ?></p>
<?php } ?>
<?php if ($args['feature'] !== null) { ?>
    <div class="ys:mb-20">
        <?php $args['loader']->part('story-feature', $args['feature']); ?>
    </div>
<?php } ?>
<?php if ($args['stories'] !== []) { ?>
    <?php $args['loader']->part('story-grid', [
        'stories' => $args['stories'],
        'column_class' => 'ys:sm:grid-cols-2 ys:md:grid-cols-3',
    ]); ?>
<?php } ?>
<?php $args['loader']->part('pagination', ['links' => $args['pagination']]); ?>
