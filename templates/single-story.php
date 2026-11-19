<?php
/**
 * Organism: single story page. Override by copying to {theme}/yourstoryz/single-story.php.
 */

use YourStoryz\Frontend\TemplateLoader;
use YourStoryz\Frontend\VideoRenderer;
use YourStoryz\StoryMeta;

defined('ABSPATH') || exit;

get_header();
$loader = new TemplateLoader;
?>
<main class="yourstoryz ys:mx-auto ys:w-full ys:max-w-3xl! ys:px-4 ys:py-8 ys:box-border">
    <?php while (have_posts()) {
        the_post();
        $postId = get_the_ID();
        $tags = array_map(
            fn (WP_Term $tag): array => ['name' => $tag->name, 'url' => (string) get_tag_link($tag)],
            get_the_tags() ?: [],
        );
        ?>
        <article class="ys:flex ys:flex-col ys:gap-6">
            <h1 class="ys:m-0"><?php the_title(); ?></h1>
            <?php echo (new VideoRenderer)->renderPost($postId); ?>
            <?php $loader->part('author-line', ['author' => (string) get_post_meta($postId, StoryMeta::AUTHOR, true)]); ?>
            <div class="ys:w-full"><?php the_content(); ?></div>
            <?php $loader->part('tag-list', ['tags' => $tags]); ?>
        </article>
    <?php } ?>
</main>
<?php
get_footer();
