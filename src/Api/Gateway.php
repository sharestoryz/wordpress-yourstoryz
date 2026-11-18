<?php

declare(strict_types=1);

namespace YourStoryz\Api;

use Closure;
use YourStoryz\Vendor\Saloon\Exceptions\Request\FatalRequestException;
use YourStoryz\Vendor\Saloon\Exceptions\Request\RequestException;
use YourStoryz\Vendor\Saloon\Exceptions\SaloonException;
use YourStoryz\Vendor\YourStoryz\PhpSdk\YourStoryz;

final class Gateway
{
    public const TIMEOUT = 15;

    /**
     * @param  (Closure(string): YourStoryz)|null  $connectorFactory
     */
    public function __construct(
        private readonly string $token,
        private readonly ?Closure $connectorFactory = null,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function me(): array
    {
        try {
            return $this->connector()
                ->users()
                ->me()
                ->json();
        } catch (FatalRequestException $exception) {
            throw new ApiException(ApiException::KIND_UNREACHABLE, 0, $exception->getMessage(), $exception);
        } catch (RequestException $exception) {
            $status = $exception->getResponse()->status();

            throw new ApiException(
                $status === 401 ? ApiException::KIND_UNAUTHORIZED : ApiException::KIND_HTTP,
                $status,
                $exception->getMessage(),
                $exception,
            );
        } catch (SaloonException $exception) {
            throw new ApiException(ApiException::KIND_UNREACHABLE, 0, $exception->getMessage(), $exception);
        }
    }

    private function connector(): YourStoryz
    {
        $connector = $this->connectorFactory
            ? ($this->connectorFactory)($this->token)
            : new YourStoryz($this->token);

        $connector->config()->add('timeout', self::TIMEOUT);
        $connector->config()->add('connect_timeout', self::TIMEOUT);

        return $connector;
    }
}
