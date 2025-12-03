<?php

declare(strict_types=1);

namespace Saloon\Http\PendingRequest;

use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;

class AuthenticatePendingRequest
{
    /**
     * Authenticate the pending request
     */
    public function __invoke(PendingRequest $pendingRequest): PendingRequest
    {
        $authenticator = $pendingRequest->getAuthenticator();

        if ($authenticator instanceof Authenticator) {
            $authenticator->set($pendingRequest);
        }

        return $pendingRequest;
    }
}
