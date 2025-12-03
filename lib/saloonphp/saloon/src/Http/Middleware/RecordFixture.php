<?php

declare(strict_types=1);

namespace Saloon\Http\Middleware;

use Saloon\Contracts\ResponseMiddleware;
use Saloon\Data\RecordedResponse;
use Saloon\Http\Faking\Fixture;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Response;

class RecordFixture implements ResponseMiddleware
{
    /**
     * The Fixture
     */
    protected Fixture $fixture;

    /**
     * Mock Client
     */
    protected MockClient $mockClient;

    /**
     * Constructor
     */
    public function __construct(Fixture $fixture, MockClient $mockClient)
    {
        $this->fixture = $fixture;
        $this->mockClient = $mockClient;
    }

    /**
     * Store the response
     */
    public function __invoke(Response $response): void
    {
        $this->fixture->store(
            RecordedResponse::fromResponse($response)
        );

        $this->mockClient->recordResponse($response);
    }
}
