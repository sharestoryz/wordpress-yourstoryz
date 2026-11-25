<?php
/**
 * Organism: one story with title, video, author, content and tags.
 *
 * @var array{post_id: int, title: string, content: string, heading_level: int, loader: TemplateLoader} $args  loader renders the author line and tags; content is trusted HTML; heading_level is 1 or 2.
 */

use YourStoryz\Frontend\TemplateLoader;
use YourStoryz\Frontend\VideoRenderer;
use YourStoryz\StoryMeta;

defined('ABSPATH') || exit;

$heading = $args['heading_level'] === 1 ? 'h1' : 'h2';
$tags = array_map(
    fn (WP_Term $tag): array => ['name' => $tag->name, 'url' => (string) get_tag_link($tag)],
    get_the_tags($args['post_id']) ?: [],
);
?>
<article class="ys:flex ys:flex-col ys:gap-6">
    <<?php echo $heading; ?> class="ys:m-0"><?php echo esc_html($args['title']); ?></<?php echo $heading; ?>>
    <?php echo (new VideoRenderer)->renderPost($args['post_id']); ?>
    <?php $args['loader']->part('author-line', ['author' => (string) get_post_meta($args['post_id'], StoryMeta::AUTHOR, true)]); ?>
    <div class="ys:w-full"><?php echo $args['content']; ?></div>
    <?php $args['loader']->part('tag-list', ['tags' => $tags]); ?>
</article>
