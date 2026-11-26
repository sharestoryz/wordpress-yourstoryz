<?php
/**
 * Atom: author line with a camera icon.
 *
 * @var array{author: string} $args
 */
defined('ABSPATH') || exit;

if ($args['author'] === '') {
    return;
}
?>
<p class="ys:m-0 ys:inline-flex ys:items-center ys:gap-2 ys:text-sm ys:font-semibold">
    <span class="ys:grid ys:size-7 ys:shrink-0 ys:place-items-center ys:rounded-full ys:bg-(--ys-tint)" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="15" height="15"><path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h9A1.5 1.5 0 0 1 15 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 3 16.5zM15 10.5l5-3v9l-5-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
    </span>
    <?php echo esc_html(sprintf(__('Video door: %s', 'yourstoryz'), $args['author'])); ?>
</p>
