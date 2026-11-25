<?php
/**
 * Molecule: linked card with thumbnail and title.
 *
 * @var array{url: string, title: string, thumbnail_url: string} $args  thumbnail_url may be empty.
 */
defined('ABSPATH') || exit;
?>
<article class="ys:overflow-hidden ys:rounded-lg ys:border ys:border-neutral-200">
    <a class="ys:block ys:text-inherit ys:no-underline" href="<?php echo esc_url($args['url']); ?>">
        <?php if ($args['thumbnail_url'] !== '') { ?>
            <img class="ys:block ys:aspect-video ys:w-full ys:object-cover" src="<?php echo esc_url($args['thumbnail_url']); ?>" alt="" loading="lazy">
        <?php } else { ?>
            <div class="ys:aspect-video ys:w-full ys:bg-neutral-200"></div>
        <?php } ?>
        <h2 class="ys:m-0 ys:p-4 ys:text-lg ys:leading-snug ys:break-words"><?php echo esc_html($args['title']); ?></h2>
    </a>
</article>
