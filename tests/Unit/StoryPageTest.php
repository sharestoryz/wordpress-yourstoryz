<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use YourStoryz\Frontend\StoryPage;
use YourStoryz\Frontend\TemplateLoader;
use YourStoryz\StoryMeta;

function yourstoryzStoryPage(bool $editing = false): StoryPage
{
    $directory = dirname(__DIR__, 2).'/';

    return new StoryPage(new TemplateLoader($directory), $directory, $editing);
}

function yourstoryzPagePost(int $id, string $title): WP_Post
{
    $post = new WP_Post;
    $post->ID = $id;
    $post->post_type = 'yourstoryz_story';
    $post->post_title = $title;
    $post->post_content = '<p>Inhoud '.$id.'</p>';

    return $post;
}

/**
 * @param  list<WP_Post>  $posts
 */
function yourstoryzArchiveQuery(array $posts, int $found): WP_Query
{
    $query = new WP_Query([]);
    $query->posts = $posts;
    $query->found_posts = $found;

    return $query;
}

function yourstoryzTag(string $name): WP_Term
{
    $tag = new WP_Term;
    $tag->name = $name;

    return $tag;
}

beforeEach(function () {
    yourstoryzWpState([
        'thumbnail' => 'https://cdn.test/p.jpg',
        'meta' => [
            StoryMeta::VIDEO_URL => 'https://cdn.test/a.mp4',
            StoryMeta::ORIENTATION => 'vertical',
            StoryMeta::LANG => 'en',
            StoryMeta::AUTHOR => 'John Wittmaekers',
        ],
    ]);
    WP_Query::$stories = [];
    $GLOBALS['yourstoryz_page_posts'] = [12 => yourstoryzPagePost(12, 'Day Three at IBC')];
    $GLOBALS['yourstoryz_paged'] = 0;
    Functions\when('esc_html')->alias(fn (string $text): string => htmlspecialchars($text));
    Functions\when('esc_html__')->returnArg();
    Functions\when('_n')->alias(fn (string $single, string $plural, int $count): string => $count === 1 ? $single : $plural);
    Functions\when('get_option')->justReturn('0');
    Functions\when('locate_template')->justReturn('');
    Functions\when('load_template')->alias(function (string $file, bool $once, array $args): void {
        require $file;
    });
    Functions\when('get_post')->alias(fn (int $id): ?WP_Post => $GLOBALS['yourstoryz_page_posts'][$id] ?? null);
    Functions\when('get_permalink')->alias(fn (WP_Post $post): string => 'https://example.test/stories/'.$post->ID.'/');
    Functions\when('get_the_title')->alias(fn (WP_Post $post): string => $post->post_title);
    Functions\when('get_the_tags')->justReturn([yourstoryzTag('en'), yourstoryzTag('IBC2025')]);
    Functions\when('get_tag_link')->alias(fn (WP_Term $tag): string => 'https://example.test/tag/'.strtolower($tag->name).'/');
    Functions\when('get_post_type_archive_link')->justReturn('https://example.test/stories/');
    Functions\when('setup_postdata')->justReturn(true);
    Functions\when('get_query_var')->alias(fn (): int => $GLOBALS['yourstoryz_paged']);
    Functions\when('post_type_archive_title')->justReturn('Stories');
    Functions\when('get_the_excerpt')->justReturn('Visitors experienced live demos.');
    Functions\when('wp_strip_all_tags')->returnArg();
    Functions\when('wp_trim_words')->returnArg();
    Functions\when('paginate_links')->justReturn(['<span class="page-numbers current">1</span>', '<a class="page-numbers" href="/stories/page/2/">2</a>']);
    Functions\when('wp_kses_post')->returnArg();
});

it('registers the template block from its directory', function () {
    Actions\expectAdded('init')->once();
    $registrations = yourstoryzCaptureBlockRegistrations();

    yourstoryzStoryPage()->register();
    yourstoryzStoryPage()->registerBlock();

    expect($registrations[0][0])->toEndWith('/blocks/story-page')
        ->and($registrations[0][1])->toBe(['render_callback']);
});

it('shows a placeholder in the editor instead of querying', function () {
    expect(yourstoryzStoryPage(true)->render(['view' => 'archive']))->toContain('Story-overzicht')
        ->and(yourstoryzStoryPage(true)->render())->toContain('Story');
});

it('renders a story with a back link, its meta, the video and related stories', function () {
    WP_Query::$stories = [yourstoryzPagePost(20, 'An Inside Look at SES')];

    $html = yourstoryzStoryPage()->single(12);

    expect($html)->toContain('href="https://example.test/stories/"')
        ->toContain('>Day Three at IBC</h1>')
        ->toContain('Video door: John Wittmaekers')
        ->toContain('<time datetime="2025-01-07T10:00:00+00:00">7 januari 2025</time>')
        ->toContain('<span class="ys-lang">EN</span>')
        ->toContain('data-ys-video')
        ->toContain('<p>Inhoud 12</p>')
        ->toContain('Meer stories')
        ->toContain('>An Inside Look at SES</h3>')
        ->and(WP_Query::$lastArgs)->toMatchArray(['post__not_in' => [12], 'posts_per_page' => 3, 'post_status' => 'publish']);
});

it('puts a vertical video in its own column', function () {
    expect(yourstoryzStoryPage()->single(12))->toContain('ys:@3xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]');
});

it('shows the tags without the language tag', function () {
    $html = yourstoryzStoryPage()->single(12);

    expect($html)->toContain('>#IBC2025</a>')->not->toContain('>#en</a>');
});

it('renders nothing for a post that is not a story', function () {
    $GLOBALS['yourstoryz_page_posts'][5] = yourstoryzPagePost(5, 'Pagina');
    $GLOBALS['yourstoryz_page_posts'][5]->post_type = 'page';

    expect(yourstoryzStoryPage()->single(5))->toBe('')
        ->and(yourstoryzStoryPage()->single(999))->toBe('');
});

it('features the newest story on the first page of the overview', function () {
    $query = yourstoryzArchiveQuery([yourstoryzPagePost(1, 'Nieuwste'), yourstoryzPagePost(2, 'Tweede')], 63);

    $html = yourstoryzStoryPage()->archive($query);

    expect($html)->toContain('>Stories</h1>')
        ->toContain('63 stories')
        ->toContain('Nieuwste</a>')
        ->toContain('Visitors experienced live demos.')
        ->toContain('>Tweede</h2>')
        ->toContain('<span class="page-numbers current">1</span>');
});

it('shows every story as a card after the first page', function () {
    $GLOBALS['yourstoryz_paged'] = 2;
    $query = yourstoryzArchiveQuery([yourstoryzPagePost(8, 'Achtste')], 9);

    $html = yourstoryzStoryPage()->archive($query);

    expect($html)->toContain('>Achtste</h2>')->not->toContain('Nieuwste');
});

it('says so when there are no stories', function () {
    expect(yourstoryzStoryPage()->archive(yourstoryzArchiveQuery([], 0)))->toContain('Geen stories gevonden.');
});

it('drops an opening heading that repeats the title', function () {
    $GLOBALS['yourstoryz_page_posts'][12]->post_content = "\n<h2 class=\"wp-block-heading\">Day Three at IBC</h2>\n<p>Inhoud</p>";
    Functions\when('wp_strip_all_tags')->alias(fn (string $text): string => strip_tags($text));

    expect(yourstoryzStoryPage()->single(12))->not->toContain('wp-block-heading')->toContain('<p>Inhoud</p>');
});

it('keeps an opening heading that differs from the title', function () {
    $GLOBALS['yourstoryz_page_posts'][12]->post_content = '<h2>Een andere kop</h2><p>Inhoud</p>';
    Functions\when('wp_strip_all_tags')->alias(fn (string $text): string => strip_tags($text));

    expect(yourstoryzStoryPage()->single(12))->toContain('<h2>Een andere kop</h2>');
});
