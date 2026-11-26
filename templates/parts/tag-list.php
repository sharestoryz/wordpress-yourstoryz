<?php
/**
 * Atom: list of tag links.
 *
 * @var array{tags: list<array{name: string, url: string}>} $args
 */
defined('ABSPATH') || exit;

if ($args['tags'] === []) {
    return;
}
?>
<ul class="ys:m-0 ys:flex ys:list-none ys:flex-wrap ys:gap-2 ys:p-0">
    <?php foreach ($args['tags'] as $tag) { ?>
        <li class="ys:m-0">
            <a class="ys-chip" href="<?php echo esc_url($tag['url']); ?>">#<?php echo esc_html($tag['name']); ?></a>
        </li>
    <?php } ?>
</ul>
