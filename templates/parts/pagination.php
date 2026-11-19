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
<nav class="ys:mt-10 ys:flex ys:flex-wrap ys:justify-center ys:gap-2 ys:[&_.page-numbers]:inline-block ys:[&_.page-numbers]:rounded ys:[&_.page-numbers]:border ys:[&_.page-numbers]:border-neutral-300 ys:[&_.page-numbers]:px-3 ys:[&_.page-numbers]:py-1 ys:[&_.page-numbers]:no-underline" aria-label="<?php echo esc_attr__('Paginering', 'yourstoryz'); ?>">
    <?php foreach ($args['links'] as $link) {
        echo wp_kses_post($link);
    } ?>
</nav>
