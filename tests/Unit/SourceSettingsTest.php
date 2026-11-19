<?php

use Brain\Monkey\Functions;
use YourStoryz\Api\ApiException;
use YourStoryz\Api\Gateway;
use YourStoryz\Api\GatewayProvider;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;
use YourStoryz\Vendor\Saloon\Http\Faking\MockClient;
use YourStoryz\Vendor\Saloon\Http\Faking\MockResponse;
use YourStoryz\Vendor\YourStoryz\PhpSdk\Requests\Companies\GetCompanies;
use YourStoryz\Vendor\YourStoryz\PhpSdk\Requests\Companies\GetDepartments;
use YourStoryz\Vendor\YourStoryz\PhpSdk\YourStoryz;

beforeEach(function () {
    $this->wpOptions = ['yourstoryz_token' => 'secret1234'];
    $this->transients = [];
    $this->ttls = [];

    Functions\when('get_option')->alias(fn ($key, $default = false) => $this->wpOptions[$key] ?? $default);
    Functions\when('update_option')->alias(function ($key, $value, $autoload = null) {
        $this->wpOptions[$key] = $value;
        $this->autoload = $autoload;

        return true;
    });
    Functions\when('get_transient')->alias(fn ($key) => $this->transients[$key] ?? false);
    Functions\when('set_transient')->alias(function ($key, $value, $ttl = 0) {
        $this->transients[$key] = $value;
        $this->ttls[$key] = $ttl;

        return true;
    });
    Functions\when('delete_transient')->alias(function ($key) {
        unset($this->transients[$key]);

        return true;
    });

    $this->requests = 0;
    $this->source = function (array $mocks) {
        return new SourceSettings(new GatewayProvider(new Settings, function (string $token) use ($mocks) {
            return new Gateway($token, function (string $token) use ($mocks) {
                $this->requests++;

                return (new YourStoryz($token))->withMockClient(new MockClient($mocks));
            });
        }));
    };
    $this->mocks = [
        GetCompanies::class => MockResponse::make(['data' => [['id' => 116, 'name' => 'Concept7']]]),
        GetDepartments::class => MockResponse::make(['data' => [['id' => 5, 'name' => 'CES'], ['id' => 6, 'name' => 'C7 Academy']]]),
    ];
});

it('caches companies in a transient for ten minutes', function () {
    $source = ($this->source)($this->mocks);

    expect($source->companies())->toBe([['id' => 116, 'name' => 'Concept7']]);
    $source->companies();

    expect($this->requests)->toBe(1)
        ->and(array_values($this->ttls))->toBe([600]);
});

it('caches departments per company', function () {
    $source = ($this->source)($this->mocks);

    $source->departments(116);
    $source->departments(116);

    expect($this->requests)->toBe(1)
        ->and(array_keys($this->transients))->toHaveCount(1);
});

it('saves a valid company and department without autoload', function () {
    ($this->source)($this->mocks)->save(116, 6);

    expect($this->wpOptions['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => 6])
        ->and($this->autoload)->toBeFalse();
});

it('saves a company without department', function () {
    expect(($this->source)($this->mocks)->save(116, null))->toBeTrue()
        ->and($this->wpOptions['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => null]);
});

it('rejects an unknown company or department and keeps the stored value', function (int $company, ?int $department) {
    $this->wpOptions['yourstoryz_source'] = ['company_id' => 116, 'department_id' => null];

    expect(($this->source)($this->mocks)->save($company, $department))->toBeFalse()
        ->and($this->wpOptions['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => null]);
})->with([
    'unknown company' => [999, null],
    'unknown department' => [116, 999],
]);

it('keeps the stored value on an API error', function () {
    $this->wpOptions['yourstoryz_source'] = ['company_id' => 116, 'department_id' => 5];
    $source = ($this->source)([GetCompanies::class => MockResponse::make([], 500)]);

    expect(fn () => $source->save(116, null))->toThrow(ApiException::class)
        ->and($this->wpOptions['yourstoryz_source'])->toBe(['company_id' => 116, 'department_id' => 5]);
});

it('clears the cached lists on refresh', function () {
    $source = ($this->source)($this->mocks);
    $source->companies();
    $source->departments(116);

    $source->refresh();

    expect($this->transients)->toBe([]);
});
