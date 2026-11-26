<?php
/**
 * Molecule: the newest story, large, with an excerpt and its tags.
 *
 * @var array{url: string, title: string, thumbnail_url: string, date: string, date_iso: string, lang: string, author: string, orientation: string, excerpt: string, tags: list<array{name: string, url: string}>, loader: TemplateLoader} $args  As story-card, plus excerpt (plain text, may be empty) and tags.
 */

use YourStoryz\Frontend\TemplateLoader;

defined('ABSPATH') || exit;

$vertical = $args['orientation'] === 'vertical';
?>
<article class="ys-card ys:grid ys:grid-cols-1 ys:items-center ys:gap-8 ys:md:grid-cols-[7fr_5fr] ys:md:gap-12">
    <a class="ys-media ys:relative ys:block ys:aspect-video ys:overflow-hidden ys:rounded-xl ys:bg-(--ys-stage)" href="<?php echo esc_url($args['url']); ?>" tabindex="-1" aria-hidden="true">
        <?php if ($args['thumbnail_url'] !== '') { ?>
            <img class="ys-media__image <?php echo $vertical ? 'ys:absolute ys:inset-y-0 ys:left-1/2 ys:h-full ys:w-auto ys:aspect-9/16 ys:-translate-x-1/2' : 'ys:absolute ys:inset-0 ys:size-full'; ?> ys:object-cover" src="<?php echo esc_url($args['thumbnail_url']); ?>" alt="">
        <?php } ?>
        <?php $args['loader']->part('play-icon', ['class' => 'ys:absolute ys:bottom-6 ys:left-6 ys:size-16']); ?>
        <span class="ys:absolute ys:left-6 ys:top-6 ys:rounded-full ys:bg-(--ys-accent-2) ys:px-3 ys:py-1.5 ys:text-xs ys:font-extrabold ys:uppercase ys:tracking-wider ys:text-white"><?php echo esc_html__('Nieuwste', 'yourstoryz'); ?></span>
    </a>
    <div class="ys:flex ys:flex-col ys:gap-4">
        <?php $args['loader']->part('story-meta', $args); ?>
        <h2 class="ys:m-0 ys:text-3xl ys:leading-tight ys:font-extrabold ys:tracking-tight ys:text-balance ys:md:text-4xl">
            <a class="ys-card__title ys:text-inherit ys:no-underline" href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['title']); ?></a>
        </h2>
        <?php if ($args['excerpt'] !== '') { ?>
            <p class="ys:m-0 ys:text-lg ys:leading-relaxed ys:text-(--ys-muted)"><?php echo esc_html($args['excerpt']); ?></p>
        <?php } ?>
        <?php if ($args['author'] !== '') { ?>
            <p class="ys:m-0 ys:text-sm ys:font-semibold"><?php echo esc_html(sprintf(__('Video door: %s', 'yourstoryz'), $args['author'])); ?></p>
        <?php } ?>
        <?php $args['loader']->part('tag-list', ['tags' => $args['tags']]); ?>
    </div>
</article>
