<?php
/**
 * Organism: one story with title, author, date, video, content and tags.
 *
 * A vertical video sits in a dark column beside the text once the story is wide enough (container query);
 * a horizontal video spans the full width between the header and the text.
 *
 * @var array{post_id: int, title: string, content: string, heading_level: int, back_url?: string, loader: TemplateLoader} $args  loader (added by TemplateLoader::part) renders the parts; content is trusted HTML; heading_level is 1 or 2; back_url links to the overview when set.
 */

use YourStoryz\Frontend\StoryCard;
use YourStoryz\Frontend\TemplateLoader;
use YourStoryz\Frontend\VideoRenderer;
use YourStoryz\StoryMeta;

defined('ABSPATH') || exit;

$postId = $args['post_id'];
$heading = $args['heading_level'] === 1 ? 'h1' : 'h2';
$vertical = StoryCard::orientation($postId) === 'vertical';
$lang = (string) get_post_meta($postId, StoryMeta::LANG, true);
$backUrl = $args['back_url'] ?? '';
$tags = StoryCard::tags($postId);
$video = (new VideoRenderer)->renderPost($postId);
?>
<article class="ys-story ys:@container">
    <div class="<?php echo $vertical ? 'ys:grid ys:items-start ys:gap-8 ys:@3xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)] ys:@3xl:gap-x-16' : 'ys:flex ys:flex-col ys:gap-10'; ?>">
        <header class="ys:flex ys:flex-col ys:gap-5 <?php echo $vertical ? 'ys:@3xl:col-start-2' : 'ys:mx-auto ys:w-full ys:max-w-4xl'; ?>">
            <?php if ($backUrl !== '') { ?>
                <a class="ys-back" href="<?php echo esc_url($backUrl); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php echo esc_html__('Alle stories', 'yourstoryz'); ?>
                </a>
            <?php } ?>
            <<?php echo $heading; ?> class="ys:m-0 ys:text-4xl ys:leading-tight ys:font-extrabold ys:tracking-tight ys:text-balance ys:break-words ys:@3xl:text-5xl"><?php echo esc_html($args['title']); ?></<?php echo $heading; ?>>
            <div class="ys:flex ys:flex-wrap ys:items-center ys:gap-x-4 ys:gap-y-2">
                <?php $args['loader']->part('author-line', ['author' => (string) get_post_meta($postId, StoryMeta::AUTHOR, true)]); ?>
                <?php $args['loader']->part('story-meta', [
                    'date' => (string) get_the_date('', $postId),
                    'date_iso' => (string) get_the_date('c', $postId),
                    'lang' => strtoupper($lang),
                ]); ?>
            </div>
        </header>
        <?php if ($video !== '') { ?>
            <div class="<?php echo $vertical ? 'ys:rounded-xl ys:bg-(--ys-stage) ys:p-5 ys:@3xl:sticky ys:@3xl:top-6 ys:@3xl:col-start-1 ys:@3xl:row-span-2 ys:@3xl:row-start-1 ys:@3xl:p-7' : ''; ?>">
                <?php echo $video; ?>
            </div>
        <?php } ?>
        <div class="ys:flex ys:flex-col ys:gap-10 <?php echo $vertical ? 'ys:@3xl:col-start-2' : 'ys:mx-auto ys:w-full ys:max-w-2xl'; ?>">
            <div class="ys-prose"><?php echo $args['content']; ?></div>
            <?php if ($tags !== []) { ?>
                <footer class="ys-rule ys:pt-8">
                    <?php $args['loader']->part('tag-list', ['tags' => $tags]); ?>
                </footer>
            <?php } ?>
        </div>
    </div>
</article>
