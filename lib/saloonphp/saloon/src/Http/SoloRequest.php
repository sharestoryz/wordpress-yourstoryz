<?php

declare(strict_types=1);

namespace Saloon\Http;

use Saloon\Http\Connectors\NullConnector;
use Saloon\Traits\Request\HasConnector;

abstract class SoloRequest extends Request
{
    use HasConnector;

    /**
     * Create a new connector instance.
     */
    protected function resolveConnector(): Connector
    {
        return new NullConnector;
    }
}
