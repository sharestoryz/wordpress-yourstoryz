<?php

declare(strict_types=1);

namespace Saloon\Traits\RequestProperties;

use Saloon\Contracts\ArrayStore as ArrayStoreContract;
use Saloon\Repositories\ArrayStore;

trait HasHeaders
{
    /**
     * Request Headers
     */
    protected ArrayStoreContract $headers;

    /**
     * Access the headers
     */
    public function headers(): ArrayStoreContract
    {
        return $this->headers ??= new ArrayStore($this->defaultHeaders());
    }

    /**
     * Default Request Headers
     *
     * @return array<string, mixed>
     */
    protected function defaultHeaders(): array
    {
        return [];
    }
}
