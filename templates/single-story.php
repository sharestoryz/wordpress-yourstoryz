<?php
/**
 * Single story page. Override by copying to {theme}/yourstoryz/single-story.php,
 * or override only a part, e.g. {theme}/yourstoryz/parts/story-article.php.
 */

use YourStoryz\Frontend\StoryPage;

defined('ABSPATH') || exit;

get_header();
?>
<main>
    <?php echo (new StoryPage)->single((int) get_queried_object_id()); ?>
</main>
<?php
get_footer();
