<?php

declare(strict_types=1);

namespace Saloon\Http\OAuth2;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use Saloon\Traits\Plugins\AcceptsJson;

class GetUserRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasFormBody;

    /**
     * Define the method that the request will use.
     */
    protected Method $method = Method::GET;

    /**
     * Define the endpoint for the request.
     */
    public function resolveEndpoint(): string
    {
        return $this->oauthConfig->getUserEndpoint();
    }

    /**
     * Requires the authorization code and OAuth 2 config.
     */
    public function __construct(protected OAuthConfig $oauthConfig)
    {
        //
    }
}
