<?php

declare(strict_types=1);

namespace Saloon\Traits\RequestProperties;

use Saloon\Contracts\ArrayStore as ArrayStoreContract;
use Saloon\Repositories\ArrayStore;

trait HasQuery
{
    /**
     * Request Query Parameters
     */
    protected ArrayStoreContract $query;

    /**
     * Access the query parameters
     */
    public function query(): ArrayStoreContract
    {
        return $this->query ??= new ArrayStore($this->defaultQuery());
    }

    /**
     * Default Query Parameters
     *
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return [];
    }
}
