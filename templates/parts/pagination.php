<?php
/**
 * Atom: pagination links.
 *
 * @var array{links: list<string>} $args  Links as returned by paginate_links(['type' => 'array']), already escaped.
 */
defined('ABSPATH') || exit;

if ($args['links'] === []) {
    return;
}
?>
<nav class="ys-pagination ys:mt-16 ys:flex ys:flex-wrap ys:items-center ys:justify-center ys:gap-1.5" aria-label="<?php echo esc_attr__('Paginering', 'yourstoryz'); ?>">
    <?php foreach ($args['links'] as $link) {
        echo wp_kses_post($link);
    } ?>
</nav>
