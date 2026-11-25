# YourStoryz WordPress plugin

Connects a WordPress site to a YourStoryz account. Admin menu **YourStoryz**:

- **Instellingen**: API token, connection test, source (company and optional department).
- **Voorbeeld**: read-only list of the video stories of the selected source, plus the **Nu importeren** button.
- **Alle stories**: the imported stories (post type `yourstoryz_story`, public at `/stories/`).

The **Autoplay** setting (off by default) makes the story video start by itself, muted and inline, in the classic template and the `yourstoryz/video` block.

Stories are imported daily by WP-Cron (`yourstoryz_import`) or on demand. Existing posts are never overwritten and permanently deleted stories are not re-imported.

Requires WordPress 6.4+ and PHP 8.3+. The entry file `YourStoryz.php` and `uninstall.php` stay parseable on PHP 7.4 so older installs get a notice instead of a fatal error.

## Development

```bash
composer install          # installs dependencies and runs Strauss (post-install hook)
composer prefix-namespaces # re-run Strauss: prefixes the SDK into vendor-prefixed/ (YourStoryz\Vendor\)
composer test             # Pest (use XDEBUG_MODE=off for speed)
composer format           # Pint
composer build            # bin/build-zip.sh -> build/yourstoryz-<version>.zip
npm ci && npm run build   # Tailwind v4 -> assets/build/frontend.css
```

`assets/build/frontend.css` is gitignored and loaded only on story pages. Run the npm build before testing the front end; `bin/build-zip.sh` builds it for the release zip.

## Front end

### Tailwind

Tailwind v4 with `prefix(ys)`: all classes carry the `ys:` prefix (`ys:flex`, `ys:text-lg`), no preflight, so the theme is not affected. The stylesheet scans only `templates/` and `src/Frontend/` (`source(none)` plus `@source`). Add new classes there and rebuild.

### Theme override (classic themes)

Copy a template to your theme and edit it:

```
{theme}/yourstoryz/single-story.php
{theme}/yourstoryz/archive-story.php
{theme}/yourstoryz/parts/<name>.php   (author-line, pagination, story-card, tag-list)
```

### Block themes

On WordPress 6.7+ the plugin registers the block templates `single-yourstoryz_story` and `archive-yourstoryz_story`, editable in the site editor, and the blocks `yourstoryz/video`, `yourstoryz/stories` and `yourstoryz/story`. The shortcodes `[yourstoryz_stories count columns tag archive_link]` and `[yourstoryz_story id]` render the same blocks through `render_block()`. On WordPress 6.4-6.6 block themes get the video prepended to the story content.

`vendor-prefixed/` is generated and must exist for the plugin to load. The release zip contains `YourStoryz.php`, `uninstall.php`, `readme.txt`, `src/`, `templates/`, `blocks/`, `vendor-prefixed/` and `assets/` (including `assets/build`).

The archive and single url slug is the `yourstoryz_slug` option (default `stories`), set on the settings page. A change sets `yourstoryz_flush_rewrite`; `PostType` flushes the rewrite rules once on the next `init` after re-registering the post type.

## Local site (lerd)

The test site is `https://yourstoryz-wp.test` (PHP 8.4).

1. Symlink this directory into the site: `ln -s <path-to>/wordpress-yourstoryz <site>/wp-content/plugins/yourstoryz`.
2. Run `composer install` in the plugin directory so `vendor-prefixed/` exists.
3. Activate **YourStoryz** under Plugins and open the YourStoryz menu.

## Structure

```
YourStoryz.php      Plugin header, PHP version guard, bootstrap
uninstall.php       Removes options (incl. legacy), transients and cron events on deletion
src/Upgrade/        Upgrader: carries 1.3.2 and 2.0.x settings over on update
src/Plugin.php      Wires hooks and boots the admin
src/Settings.php    API token option (yourstoryz_token)
src/SourceSettings.php  Company/department option (yourstoryz_source) and cached lists
src/Source.php      Value object for the selected source
src/StoryCatalog.php    Fetches and caches stories
src/PostType.php    Post type yourstoryz_story and its (configurable) rewrite slug
src/ReservedSlugs.php   Checks a story slug against WordPress, pages, post types and taxonomies
src/Import/         Importer, runner (cron + lock), post planner, deleted-story registry
src/Frontend/       Template loader, block templates, video/stories/story blocks, editor-request detection, shortcodes, assets
templates/          Classic theme templates and parts (theme-overridable)
blocks/             Block definitions (yourstoryz/video, stories, story)
src/Admin/          Menu, settings page, source section, preview page, notices
src/Api/            Gateway around the SDK, story mapper, DTOs
tests/Unit/         Pest + Brain Monkey tests
bin/build-zip.sh    Release zip
```

## Uninstall

`uninstall.php` deletes `yourstoryz_token`, `yourstoryz_source`, `yourstoryz_import_status`, `yourstoryz_auto_import`, `yourstoryz_import_interval`, `yourstoryz_autoplay`, `yourstoryz_deleted_stories`, the `yourstoryz_import_lock` option, `yourstoryz_db_version`, `yourstoryz_upgrade_notice`, `yourstoryz_legacy_imports`, the options of 1.3.2 and 2.0.x (`yourstoryz_plugin_change_token`, `yourstoryz_api_token`, `yourstoryz_storiable_type`, `yourstoryz_storiable_id`, `yourstoryz_plugin_filter_on_department`, `yourstoryz_plugin_autoplay`, `yourstoryz_enable_cron`, `yourstoryz_cron_interval`, `yourstoryz_processed_ids`) and all other `yourstoryz_*` transients (value and timeout rows), and clears the `yourstoryz_import`, `yourstoryz_cron_event` and `yourstoryz_api_fetch` cron events. Imported story posts and their attachments are kept. Multisite is not handled: on a network install only the current site is cleaned.
