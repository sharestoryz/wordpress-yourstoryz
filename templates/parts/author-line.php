<?php
/**
 * Atom: author line.
 *
 * @var array{author: string} $args
 */
defined('ABSPATH') || exit;

if ($args['author'] === '') {
    return;
}
?>
<p class="ys:m-0 ys:text-sm ys:text-neutral-600">
    <?php echo esc_html(sprintf(__('Video door: %s', 'yourstoryz'), $args['author'])); ?>
</p>
