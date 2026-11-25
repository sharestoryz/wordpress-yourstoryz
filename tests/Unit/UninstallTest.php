<?php

use Brain\Monkey\Functions;
use YourStoryz\Import\DeletedStories;
use YourStoryz\Import\ImportRunner;
use YourStoryz\Import\LegacyImports;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;
use YourStoryz\Upgrade\Upgrader;

function yourstoryz_run_uninstall(): void
{
    require dirname(__DIR__, 2).'/uninstall.php';
}

afterEach(function () {
    unset($GLOBALS['wpdb']);
});

it('does nothing without the WP_UNINSTALL_PLUGIN constant', function () {
    // The guard calls exit, and the constant may already be defined here, so run the file in a child process.
    $output = [];
    exec(PHP_BINARY.' -r '.escapeshellarg('require '.var_export(dirname(__DIR__, 2).'/uninstall.php', true).';').' 2>&1', $output, $code);

    expect($code)->toBe(0)->and($output)->toBe([]);
});

it('removes the options and the yourstoryz transients', function () {
    if (! defined('WP_UNINSTALL_PLUGIN')) {
        define('WP_UNINSTALL_PLUGIN', 'yourstoryz/YourStoryz.php');
    }

    $deleted = [];
    Functions\when('delete_option')->alias(function ($key) use (&$deleted) {
        $deleted[] = $key;

        return true;
    });

    $cleared = [];
    Functions\when('wp_clear_scheduled_hook')->alias(function ($hook) use (&$cleared) {
        $cleared[] = $hook;

        return 1;
    });

    $wpdb = Mockery::mock();
    $wpdb->options = 'wp_options';
    $wpdb->shouldReceive('esc_like')->twice()->andReturnArg(0);
    $wpdb->shouldReceive('prepare')
        ->twice()
        ->withArgs(fn (string $sql, string $pattern) => str_contains($sql, 'DELETE FROM wp_options WHERE option_name LIKE %s')
            && in_array($pattern, ['_transient_yourstoryz_%', '_transient_timeout_yourstoryz_%'], true))
        ->andReturnUsing(fn (string $sql, string $pattern) => $pattern);
    $wpdb->shouldReceive('query')->once()->with('_transient_yourstoryz_%');
    $wpdb->shouldReceive('query')->once()->with('_transient_timeout_yourstoryz_%');
    $GLOBALS['wpdb'] = $wpdb;

    yourstoryz_run_uninstall();

    $legacyUpgraderOptions = Upgrader::LEGACY_OPTIONS;

    expect($deleted)->toContain(
        Settings::OPTION_TOKEN,
        SourceSettings::OPTION,
        Settings::OPTION_IMPORT_STATUS,
        Settings::OPTION_AUTO_IMPORT,
        Settings::OPTION_IMPORT_INTERVAL,
        Settings::OPTION_AUTOPLAY,
        DeletedStories::OPTION,
        ImportRunner::LOCK,
        Upgrader::OPTION_DB_VERSION,
        Upgrader::OPTION_NOTICE,
        Upgrader::OPTION_LOCK,
        LegacyImports::OPTION,
        ...$legacyUpgraderOptions,
    )
        ->and($cleared)->toBe(['yourstoryz_import', ...Upgrader::LEGACY_CRON_HOOKS])
        ->and($cleared)->toContain(ImportRunner::HOOK);
});

it('keeps imported story posts and attachments', function () {
    if (! defined('WP_UNINSTALL_PLUGIN')) {
        define('WP_UNINSTALL_PLUGIN', 'yourstoryz/YourStoryz.php');
    }

    Functions\when('delete_option')->justReturn(true);
    Functions\when('delete_transient')->justReturn(true);
    Functions\when('wp_clear_scheduled_hook')->justReturn(1);
    Functions\expect('wp_delete_post')->never();
    Functions\expect('wp_delete_attachment')->never();

    $queries = [];
    $wpdb = Mockery::mock();
    $wpdb->options = 'wp_options';
    $wpdb->posts = 'wp_posts';
    $wpdb->postmeta = 'wp_postmeta';
    $wpdb->shouldReceive('esc_like')->andReturnArg(0);
    $wpdb->shouldReceive('prepare')->andReturnUsing(fn (string $sql, string $pattern) => $sql);
    $wpdb->shouldReceive('query')->andReturnUsing(function (string $sql) use (&$queries) {
        $queries[] = $sql;

        return 1;
    });
    $GLOBALS['wpdb'] = $wpdb;

    yourstoryz_run_uninstall();

    expect($queries)->not->toBeEmpty();
    foreach ($queries as $sql) {
        expect($sql)->toContain('wp_options')
            ->not->toContain('wp_posts')
            ->not->toContain('wp_postmeta');
    }
});
