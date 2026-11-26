<?php
/**
 * Organism: "Meer stories" below a story.
 *
 * @var array{stories: list<array<string, mixed>>, archive_url: string, loader: TemplateLoader} $args  stories as built by StoryCard::fromPost(); archive_url may be empty.
 */

use YourStoryz\Frontend\TemplateLoader;

defined('ABSPATH') || exit;

if ($args['stories'] === []) {
    return;
}
?>
<section class="ys-related ys:mt-24 ys:rounded-2xl ys:bg-(--ys-tint) ys:px-5 ys:py-12 ys:md:px-12 ys:md:py-16">
    <div class="ys:mb-10 ys:flex ys:flex-wrap ys:items-end ys:justify-between ys:gap-4">
        <h2 class="ys:m-0 ys:text-3xl ys:font-extrabold ys:tracking-tight"><?php echo esc_html__('Meer stories', 'yourstoryz'); ?></h2>
        <?php if ($args['archive_url'] !== '') { ?>
            <a class="ys-back" href="<?php echo esc_url($args['archive_url']); ?>">
                <?php echo esc_html__('Alle stories', 'yourstoryz'); ?>
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        <?php } ?>
    </div>
    <?php $args['loader']->part('story-grid', [
        'stories' => $args['stories'],
        'column_class' => 'ys:sm:grid-cols-2 ys:md:grid-cols-3',
        'heading_level' => 3,
    ]); ?>
</section>
