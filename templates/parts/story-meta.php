<?php
/**
 * Atom: publication date and language of a story.
 *
 * @var array{date: string, date_iso: string, lang: string} $args  lang may be empty.
 */
defined('ABSPATH') || exit;
?>
<p class="ys:m-0 ys:flex ys:flex-wrap ys:items-center ys:gap-2.5 ys:text-sm ys:text-(--ys-muted)">
    <time datetime="<?php echo esc_attr($args['date_iso']); ?>"><?php echo esc_html($args['date']); ?></time>
    <?php if ($args['lang'] !== '') { ?>
        <span class="ys-lang"><?php echo esc_html($args['lang']); ?></span>
    <?php } ?>
</p>
