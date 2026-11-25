<?php
/**
 * Organism: single story page. Override by copying to {theme}/yourstoryz/single-story.php.
 */

use YourStoryz\Frontend\TemplateLoader;

defined('ABSPATH') || exit;

get_header();
$loader = new TemplateLoader;
?>
<main class="yourstoryz ys:mx-auto ys:w-full ys:max-w-3xl! ys:px-4 ys:py-8 ys:box-border">
    <?php while (have_posts()) {
        the_post();
        ob_start();
        the_content();
        $content = (string) ob_get_clean();
        $loader->part('story-article', [
            'post_id' => (int) get_the_ID(),
            'title' => get_the_title(),
            'content' => $content,
            'heading_level' => 1,
            'loader' => $loader,
        ]);
    } ?>
</main>
<?php
get_footer();
