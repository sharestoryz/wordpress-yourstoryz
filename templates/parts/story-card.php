<?php
/**
 * Molecule: linked card with thumbnail, play icon, date, language, title and author.
 *
 * @var array{url: string, title: string, thumbnail_url: string, date: string, date_iso: string, lang: string, author: string, orientation: string, heading_level?: int, loader: TemplateLoader} $args  thumbnail_url, lang and author may be empty; orientation is horizontal or vertical; heading_level defaults to 2.
 */

use YourStoryz\Frontend\TemplateLoader;

defined('ABSPATH') || exit;

$heading = ($args['heading_level'] ?? 2) === 3 ? 'h3' : 'h2';
$vertical = $args['orientation'] === 'vertical';
?>
<article class="ys-card ys:min-w-0">
    <a class="ys:flex ys:flex-col ys:gap-4 ys:text-inherit ys:no-underline" href="<?php echo esc_url($args['url']); ?>">
        <div class="ys-media ys:relative ys:aspect-video ys:overflow-hidden ys:rounded-xl ys:bg-(--ys-stage)">
            <?php if ($args['thumbnail_url'] !== '') { ?>
                <img class="ys-media__image <?php echo $vertical ? 'ys:absolute ys:inset-y-0 ys:left-1/2 ys:h-full ys:w-auto ys:aspect-9/16 ys:-translate-x-1/2' : 'ys:absolute ys:inset-0 ys:size-full'; ?> ys:object-cover" src="<?php echo esc_url($args['thumbnail_url']); ?>" alt="" loading="lazy">
            <?php } ?>
            <?php $args['loader']->part('play-icon', ['class' => 'ys:absolute ys:bottom-4 ys:left-4 ys:size-11']); ?>
            <?php if ($vertical) { ?>
                <span class="ys:absolute ys:right-3 ys:top-3 ys:inline-flex ys:items-center ys:gap-1.5 ys:rounded-full ys:bg-white/90 ys:px-2.5 ys:py-1 ys:text-xs ys:font-bold ys:text-(--ys-stage)">
                    <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2.5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                    <?php echo esc_html__('Staand', 'yourstoryz'); ?>
                </span>
            <?php } ?>
        </div>
        <div class="ys:flex ys:flex-col ys:gap-2">
            <?php $args['loader']->part('story-meta', $args); ?>
            <<?php echo $heading; ?> class="ys-card__title ys:m-0 ys:text-xl ys:leading-tight ys:font-bold ys:text-balance ys:break-words"><?php echo esc_html($args['title']); ?></<?php echo $heading; ?>>
            <?php if ($args['author'] !== '') { ?>
                <p class="ys:m-0 ys:text-sm ys:text-(--ys-muted)"><?php echo esc_html(sprintf(__('Video door: %s', 'yourstoryz'), $args['author'])); ?></p>
            <?php } ?>
        </div>
    </a>
</article>
