<?php
/**
 * Organism: story archive page. Override by copying to {theme}/yourstoryz/archive-story.php.
 */

use YourStoryz\Frontend\TemplateLoader;

defined('ABSPATH') || exit;

get_header();
$loader = new TemplateLoader;
?>
<main class="yourstoryz ys:mx-auto ys:w-full ys:max-w-6xl! ys:px-4 ys:py-8 ys:box-border">
    <h1 class="ys:mb-8 ys:mt-0"><?php post_type_archive_title(); ?></h1>
    <?php if (have_posts()) { ?>
        <?php
        $stories = [];
        while (have_posts()) {
            the_post();
            $stories[] = [
                'url' => (string) get_permalink(),
                'title' => get_the_title(),
                'thumbnail_url' => (string) get_the_post_thumbnail_url(get_the_ID(), 'medium_large'),
            ];
        }
        $loader->part('story-grid', ['stories' => $stories, 'column_class' => 'ys:md:grid-cols-3', 'loader' => $loader]);
        ?>
        <?php $loader->part('pagination', ['links' => (array) paginate_links(['type' => 'array'])]); ?>
    <?php } else { ?>
        <p><?php echo esc_html__('Geen stories gevonden.', 'yourstoryz'); ?></p>
    <?php } ?>
</main>
<?php
get_footer();
