<?php

declare(strict_types=1);

namespace Saloon\Http;

use LogicException;
use Saloon\Enums\Method;
use Saloon\Traits\Auth\AuthenticatesRequests;
use Saloon\Traits\Bootable;
use Saloon\Traits\Conditionable;
use Saloon\Traits\HandlesPsrRequest;
use Saloon\Traits\HasDebugging;
use Saloon\Traits\HasMockClient;
use Saloon\Traits\Makeable;
use Saloon\Traits\ManagesExceptions;
use Saloon\Traits\Request\CreatesDtoFromResponse;
use Saloon\Traits\RequestProperties\HasRequestProperties;
use Saloon\Traits\RequestProperties\HasTries;
use Saloon\Traits\Responses\HasCustomResponses;

abstract class Request
{
    use AuthenticatesRequests;
    use Bootable;
    use Conditionable;
    use CreatesDtoFromResponse;
    use HandlesPsrRequest;
    use HasCustomResponses;
    use HasDebugging;
    use HasMockClient;
    use HasRequestProperties;
    use HasTries;
    use Makeable;
    use ManagesExceptions;

    /**
     * Define the HTTP method.
     */
    protected Method $method;

    /**
     * Get the method of the request.
     */
    public function getMethod(): Method
    {
        if (! isset($this->method)) {
            throw new LogicException('Your request is missing a HTTP method. You must add a method property like [protected Method $method = Method::GET]');
        }

        return $this->method;
    }

    /**
     * Define the endpoint for the request.
     */
    abstract public function resolveEndpoint(): string;
}
