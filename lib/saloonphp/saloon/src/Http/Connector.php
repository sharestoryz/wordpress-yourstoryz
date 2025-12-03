<?php

declare(strict_types=1);

namespace Saloon\Http;

use Saloon\Traits\Auth\AuthenticatesRequests;
use Saloon\Traits\Bootable;
use Saloon\Traits\Conditionable;
use Saloon\Traits\Connector\SendsRequests;
use Saloon\Traits\HandlesPsrRequest;
use Saloon\Traits\HasDebugging;
use Saloon\Traits\HasMockClient;
use Saloon\Traits\Makeable;
use Saloon\Traits\ManagesExceptions;
use Saloon\Traits\Request\CreatesDtoFromResponse;
use Saloon\Traits\RequestProperties\HasRequestProperties;
use Saloon\Traits\RequestProperties\HasTries;
use Saloon\Traits\Responses\HasCustomResponses;

abstract class Connector
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
    use SendsRequests;

    /**
     * Define the base URL of the API.
     */
    abstract public function resolveBaseUrl(): string;
}
