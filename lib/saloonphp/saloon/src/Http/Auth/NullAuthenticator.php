<?php

declare(strict_types=1);

namespace Saloon\Http\Auth;

use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;

class NullAuthenticator implements Authenticator
{
    /**
     * Apply the authentication to the request.
     */
    public function set(PendingRequest $pendingRequest): void
    {
        //
    }
}
