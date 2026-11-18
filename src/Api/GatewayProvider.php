<?php

declare(strict_types=1);

namespace YourStoryz\Api;

use Closure;
use YourStoryz\Settings;

final class GatewayProvider
{
    /**
     * @param  (Closure(string): Gateway)|null  $gatewayFactory
     */
    public function __construct(
        private readonly Settings $settings,
        private readonly ?Closure $gatewayFactory = null,
    ) {}

    public function gateway(): Gateway
    {
        $token = $this->settings->token();

        return $this->gatewayFactory ? ($this->gatewayFactory)($token) : new Gateway($token);
    }

    public function tokenHash(): string
    {
        return substr(md5($this->settings->token()), 0, 8);
    }
}
