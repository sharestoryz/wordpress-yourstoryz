=== YourStoryz ===
Contributors: yourstoryz, concept7
Tags: api, freemium
Requires at least: 6.4
Requires PHP: 8.3
Tested up to: 7.1
Stable tag: 1.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
The YourStoryz plugin connects your WordPress site to your YourStoryz account.

* Settings page to store your API token and test the connection.
* Choose the source: a company and, optionally, a department.
* Preview page that lists the video stories of the selected source (read-only).
* Import: stories are imported as posts of the `yourstoryz_story` post type, daily via WP-Cron or on demand with the "Nu importeren" button. Choose whether imported stories become drafts or are published immediately.
* Stories are shown at `/stories/` (archive) and `/stories/your-story/` (single), with tags and the video embedded.
* Works with classic and block themes. Classic themes can override the templates by copying them to `{theme}/yourstoryz/single-story.php`, `{theme}/yourstoryz/archive-story.php` and `{theme}/yourstoryz/parts/<name>.php`. Block themes (WordPress 6.7+) can edit the story templates in the site editor and use the YourStoryz video block.

Existing posts are never overwritten by the import, and stories you permanently delete are not imported again.

On uninstall the plugin removes its settings, the import schedule and cached data. Imported story posts and their images stay in your site.

== Installation ==
1. Upload the `yourstoryz-plugin` folder to `/wp-content/plugins/`.
2. Activate the plugin through the \'Plugins\' menu in WordPress.
3. Configure your API key and settings from the YourStoryz menu.

== Frequently Asked Questions ==
= How do I get an API key? =
Follow the instructions on the plugin settings page to obtain and enter your API key.

= Why is my API key validation failing? =
Ensure the key is correct and has required permissions.

= Existing pages are not changed =
The import only creates new story posts. It never edits your existing pages or posts, and it never overwrites a story post that was already imported, so your own edits are kept. The story templates only apply to the story post type.

= How do I change the look of a story? =
Classic themes: copy `templates/single-story.php` or `templates/archive-story.php` from the plugin to `{theme}/yourstoryz/` in your theme. Block themes: edit the "Enkele story" and "Story-overzicht" templates in the site editor.

== Third party integrations ==
This plugins relies on the Yourstoryz API to fetch your personal content. You need an API key to use this plugin.
The API key can be obtained by creating an account on the YourStoryz platform. You can sign up at https://dashboard.yourstoryz.com/en/register or via the YourStoryz app available on iOS and Android.
The terms and conditions for the API can be found at https://yourstoryz.com/wp-content/uploads/2023/12/yourstoryz-termsconditions-apple-eula-2022.pdf
The privacy policy can be found at https://yourstoryz.com/privacy-policy-cookies/

== Changelog ==
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
= 2.0.0 (in development) =
Rewrite on top of the YourStoryz PHP SDK. New settings page with token and connection test, company and department selection, and a read-only preview of video stories. Requires PHP 8.3 and WordPress 6.4. Adds uninstall cleanup.
Imports stories as posts of the `yourstoryz_story` post type (daily cron and manual import, existing posts are never overwritten, one post per story per language), with an archive at `/stories/`, theme-overridable templates for classic themes, block templates and a video block for block themes, and a prefixed Tailwind stylesheet that only loads on story pages. Uninstall removes the settings, import lock and schedule; imported posts and images are kept.
