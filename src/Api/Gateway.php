<?php

declare(strict_types=1);

namespace YourStoryz\Api;

use Closure;
use YourStoryz\Source;
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
        return $this->call(fn (YourStoryz $connector): array => $connector->users()->me()->json());
    }

    /**
     * @return list<array{id: int, name: string}>
     *
     * @throws ApiException
     */
    public function companies(): array
    {
        return $this->normalizeList(
            $this->call(fn (YourStoryz $connector): array => $connector->companies()->all()->json())
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     *
     * @throws ApiException
     */
    public function departments(int $companyId): array
    {
        return $this->normalizeList(
            $this->call(fn (YourStoryz $connector): array => $connector->companies()->departments($companyId)->json())
        );
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws ApiException
     */
    public function stories(Source $source): array
    {
        $response = $this->call(fn (YourStoryz $connector): array => $source->departmentId === null
            ? $connector->companies()->stories($source->companyId)->json()
            : $connector->departments()->stories($source->departmentId)->json());

        $items = is_array($response['data'] ?? null) ? $response['data'] : $response;

        return array_values(array_filter($items, 'is_array'));
    }

    /**
     * @param  Closure(YourStoryz): array<mixed>  $request
     * @return array<mixed>
     *
     * @throws ApiException
     */
    private function call(Closure $request): array
    {
        try {
            return $request($this->connector());
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

    /**
     * @param  array<mixed>  $response
     * @return list<array{id: int, name: string}>
     */
    private function normalizeList(array $response): array
    {
        $items = is_array($response['data'] ?? null) ? $response['data'] : $response;
        $list = [];

        foreach ($items as $item) {
            if (is_array($item) && isset($item['id'])) {
                $list[] = ['id' => (int) $item['id'], 'name' => (string) ($item['name'] ?? $item['id'])];
            }
        }

        return $list;
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
