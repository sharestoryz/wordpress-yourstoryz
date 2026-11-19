<?php

use YourStoryz\Api\ApiException;
use YourStoryz\Api\Gateway;
use YourStoryz\Vendor\Saloon\Exceptions\Request\FatalRequestException;
use YourStoryz\Vendor\Saloon\Http\Faking\MockClient;
use YourStoryz\Vendor\Saloon\Http\Faking\MockResponse;
use YourStoryz\Vendor\Saloon\Http\PendingRequest;
use YourStoryz\Vendor\YourStoryz\PhpSdk\Requests\Users\Me;
use YourStoryz\Vendor\YourStoryz\PhpSdk\YourStoryz;

function gatewayWith(MockResponse $response): Gateway
{
    return new Gateway('token', fn (string $token) => (new YourStoryz($token))->withMockClient(new MockClient([
        Me::class => $response,
    ])));
}

it('returns the decoded me response', function () {
    $me = gatewayWith(MockResponse::make(['data' => ['name' => 'Jane']], 200))->me();

    expect($me['data']['name'])->toBe('Jane');
});

it('maps 401 to an unauthorized exception', function () {
    gatewayWith(MockResponse::make(['message' => 'Unauthenticated.'], 401))->me();
})->throws(ApiException::class);

it('exposes kind and status for 401 and 500', function (int $status, string $kind) {
    try {
        gatewayWith(MockResponse::make([], $status))->me();
    } catch (ApiException $exception) {
        expect($exception->kind)->toBe($kind)
            ->and($exception->status)->toBe($status);

        return;
    }

    $this->fail('No ApiException thrown');
})->with([
    [401, ApiException::KIND_UNAUTHORIZED],
    [500, ApiException::KIND_HTTP],
]);

it('maps a connection failure to unreachable', function () {
    $response = MockResponse::make()->throw(
        fn (PendingRequest $pendingRequest) => new FatalRequestException(new RuntimeException('timed out'), $pendingRequest)
    );

    try {
        gatewayWith($response)->me();
    } catch (ApiException $exception) {
        expect($exception->kind)->toBe(ApiException::KIND_UNREACHABLE);

        return;
    }

    $this->fail('No ApiException thrown');
});
