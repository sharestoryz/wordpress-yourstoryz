<?php

declare(strict_types=1);

namespace Saloon\Traits;

use Psr\Http\Message\RequestInterface;
use Saloon\Http\PendingRequest;

trait HandlesPsrRequest
{
    /**
     * Handle the PSR request before it is sent
     */
    public function handlePsrRequest(RequestInterface $request, PendingRequest $pendingRequest): RequestInterface
    {
        return $request;
    }
}
