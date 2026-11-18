<?php

use Brain\Monkey\Functions;

beforeEach(function () {
    Functions\stubs([
        'plugin_dir_path' => fn (string $file) => dirname($file).'/',
    ]);

    require_once dirname(__DIR__, 2).'/YourStoryz.php';
});

it('rejects PHP versions below 8.3', function (string $version) {
    expect(yourstoryz_meets_requirements($version))->toBeFalse();
})->with(['7.4.33', '8.2.30']);

it('accepts PHP 8.3 and newer', function (string $version) {
    expect(yourstoryz_meets_requirements($version))->toBeTrue();
})->with(['8.3.0', '8.4.1']);

it('prints an escaped admin notice', function () {
    Functions\expect('esc_html__')
        ->once()
        ->with(Mockery::type('string'), 'yourstoryz')
        ->andReturnFirstArg();

    ob_start();
    yourstoryz_php_notice();

    expect(ob_get_clean())->toContain('notice-error')->toContain('PHP 8.3');
});
