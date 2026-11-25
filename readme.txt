=== YourStoryz ===
Contributors: yourstoryz, concept7
Tags: api, freemium
Requires at least: 6.4
Requires PHP: 8.3
Tested up to: 7.1
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
The YourStoryz plugin connects your WordPress site to your YourStoryz account.

* Settings page to store your API token and test the connection.
* Choose the source: a company and, optionally, a department.
* Preview page that lists the video stories of the selected source (read-only).
* Import: stories are imported as posts of the `yourstoryz_story` post type, on a schedule via WP-Cron (switch it on or off and pick hourly, twice daily, daily or weekly) or on demand with the "Nu importeren" button. Choose whether imported stories become drafts or are published immediately.
* Stories are shown at `/stories/` (archive) and `/stories/your-story/` (single), with tags and the video embedded.
* Works with classic and block themes. Classic themes can override the templates by copying them to `{theme}/yourstoryz/single-story.php`, `{theme}/yourstoryz/archive-story.php` and `{theme}/yourstoryz/parts/<name>.php`. Block themes (WordPress 6.7+) can edit the story templates in the site editor and use the YourStoryz video block.

= Blocks and shortcodes =
* Block "Stories-overzicht" (`yourstoryz/stories`): a grid of the newest stories. Settings: number of stories (1-24), columns (1-4), an optional tag and a link to all stories.
* Block "Story" (`yourstoryz/story`): one chosen story with video, title and content.
* Block "Story video" (`yourstoryz/video`): the video of the current story.
* Shortcode `[yourstoryz_stories count="6" columns="3" tag="" archive_link="1"]`: same output as the "Stories-overzicht" block. `tag` is a tag slug; `archive_link` accepts 1, 0, true, false, yes or no.
* Shortcode `[yourstoryz_story id="123"]`: same output as the "Story" block. `id` is the post ID of a published story.

Existing posts are never overwritten by the import, and stories you permanently delete are not imported again.

On uninstall the plugin removes its settings, the import schedule and cached data. Imported story posts and their images stay in your site.

== Installation ==
1. Upload the `yourstoryz` folder to `/wp-content/plugins/`.
2. Activate the plugin through the \'Plugins\' menu in WordPress.
3. Configure your API key and settings from the YourStoryz menu.

== Frequently Asked Questions ==
= How do I get an API key? =
Create an account at https://yourstoryz.com (or in the YourStoryz app), log in to the dashboard and go to My Companies > Tokens > API Tokens. Create a token and paste it on the plugin settings page, which repeats these steps.

= Why is my API key validation failing? =
Ensure the key is correct and has required permissions.

= Where are the thumbnails and videos stored? =
Thumbnails are saved in your media library. Videos are not stored on your site: they play from YourStoryz.

= Where can I get support? =
Contact YourStoryz support at https://yourstoryz.com/contact/.

= Existing pages are not changed =
The import only creates new story posts. It never edits your existing pages or posts, and it never overwrites a story post that was already imported, so your own edits are kept. The story templates only apply to the story post type.

= Can I change the url of the stories? =
Yes. On the settings page, change the "Url-slug" (default `stories`). The archive and the single stories then live at `/<slug>/` and `/<slug>/<story>/`. An empty slug, or a slug already used by a page, post, post type, taxonomy or WordPress itself, is refused and the previous slug is kept.

= How do I change the look of a story? =
Classic themes: copy `templates/single-story.php` or `templates/archive-story.php` from the plugin to `{theme}/yourstoryz/` in your theme. Block themes: edit the "Enkele story" and "Story-overzicht" templates in the site editor.

== Third party integrations ==
This plugins relies on the Yourstoryz API to fetch your personal content. You need an API key to use this plugin.
The API key can be obtained by creating an account on the YourStoryz platform. You can sign up at https://dashboard.yourstoryz.com/en/register or via the YourStoryz app available on iOS and Android.
The terms and conditions for the API can be found at https://yourstoryz.com/wp-content/uploads/2023/12/yourstoryz-termsconditions-apple-eula-2022.pdf
The privacy policy can be found at https://yourstoryz.com/privacy-policy-cookies/

== Changelog ==
= 3.0.0 =
Full rebuild of the plugin on top of the YourStoryz PHP SDK. Requires PHP 8.3 and WordPress 6.4.
* Stories are imported as their own post type, `yourstoryz_story`: one post per news text per language, with a Dutch fallback. Existing posts are never overwritten.
* Classic theme templates (overridable in your theme), block theme templates and a story video block.
* New settings page with token and connection test, company and department selection, a preview page with a "Nu importeren" button and a Dutch setup guide.
* Import schedule: switch it on or off and choose hourly, twice daily, daily or weekly.
* Optional muted autoplay for the story video.
* Sites upgrading from 2.0.x move from hourly to daily import by default (changeable on the settings page).
* Automatic upgrade from 1.3.x and 2.0.x: the token, source, autoplay and import schedule are kept, and stories the old version already imported are not imported again. Posts created by older versions stay as normal posts.
* Uninstall removes the settings, import lock and schedule; imported posts and images are kept.

= 1.0 =
Initial release.
= 1.1 =
Small bugfix and add support for markdown content.
= 1.2 =
Small bugfix
= 1.3 =
Small bugfix
= 1.3.1 =
Add horizontal/vertical styling for video embed
= 1.3.2 =
Typo fix

== Upgrade Notice ==
= 3.0.0 =
Major rebuild, requires PHP 8.3+ and WordPress 6.4+. Your settings are carried over automatically. Previously imported posts are kept and not imported again.
