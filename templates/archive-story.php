<?php
/**
 * Story overview. Override by copying to {theme}/yourstoryz/archive-story.php,
 * or override only a part, e.g. {theme}/yourstoryz/parts/story-card.php.
 */

use YourStoryz\Frontend\StoryPage;

defined('ABSPATH') || exit;

get_header();
?>
<main>
    <?php echo (new StoryPage)->archive($GLOBALS['wp_query']); ?>
</main>
<?php
get_footer();
