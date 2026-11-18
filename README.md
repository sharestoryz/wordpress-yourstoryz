# YourStoryz WordPress plugin

Connects a WordPress site to a YourStoryz account. Admin menu **YourStoryz**:

- **Instellingen**: API token, connection test, source (company and optional department).
- **Voorbeeld**: read-only list of the video stories of the selected source.

Requires WordPress 6.4+ and PHP 8.3+. The entry file `YourStoryz.php` and `uninstall.php` stay parseable on PHP 7.4 so older installs get a notice instead of a fatal error.

## Development

```bash
composer install          # installs dependencies and runs Strauss (post-install hook)
composer prefix-namespaces # re-run Strauss: prefixes the SDK into vendor-prefixed/ (YourStoryz\Vendor\)
composer test             # Pest (use XDEBUG_MODE=off for speed)
composer format           # Pint
composer build            # bin/build-zip.sh -> build/yourstoryz-<version>.zip
```

`vendor-prefixed/` is generated and must exist for the plugin to load. The release zip contains `YourStoryz.php`, `uninstall.php`, `readme.txt`, `src/`, `vendor-prefixed/` and `assets/`.

## Local site (lerd)

The test site is `https://yourstoryz-wp.test` (PHP 8.4).

1. Symlink this directory into the site: `ln -s <path-to>/wordpress-yourstoryz <site>/wp-content/plugins/yourstoryz`.
2. Run `composer install` in the plugin directory so `vendor-prefixed/` exists.
3. Activate **YourStoryz** under Plugins and open the YourStoryz menu.

## Structure

```
YourStoryz.php      Plugin header, PHP version guard, bootstrap
uninstall.php       Removes options and transients on deletion
src/Plugin.php      Wires hooks and boots the admin
src/Settings.php    API token option (yourstoryz_token)
src/SourceSettings.php  Company/department option (yourstoryz_source) and cached lists
src/Source.php      Value object for the selected source
src/StoryCatalog.php    Fetches and caches stories
src/Admin/          Menu, settings page, source section, preview page, notices
src/Api/            Gateway around the SDK, story mapper, DTOs
tests/Unit/         Pest + Brain Monkey tests
bin/build-zip.sh    Release zip
```

## Uninstall

`uninstall.php` deletes `yourstoryz_token`, `yourstoryz_source` and all `yourstoryz_*` transients (value and timeout rows). Multisite is not handled: on a network install only the current site is cleaned.
