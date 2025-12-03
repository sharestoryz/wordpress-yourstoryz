<?php

declare(strict_types=1);

namespace Saloon\Traits\RequestProperties;

use Saloon\Contracts\ArrayStore as ArrayStoreContract;
use Saloon\Repositories\ArrayStore;

trait HasConfig
{
    /**
     * Request Config
     */
    protected ArrayStoreContract $config;

    /**
     * Access the config
     */
    public function config(): ArrayStoreContract
    {
        return $this->config ??= new ArrayStore($this->defaultConfig());
    }

    /**
     * Default Config
     *
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [];
    }
}
