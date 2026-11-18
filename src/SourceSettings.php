<?php

declare(strict_types=1);

namespace YourStoryz;

use Closure;
use YourStoryz\Api\ApiException;
use YourStoryz\Api\Gateway;

final class SourceSettings
{
    public const OPTION = 'yourstoryz_source';

    public const TTL = 600;

    /**
     * @param  (Closure(string): Gateway)|null  $gatewayFactory
     */
    public function __construct(
        private readonly Settings $settings,
        private readonly ?Closure $gatewayFactory = null,
    ) {}

    /**
     * @return array{company_id: int, department_id: int|null}|null
     */
    public function get(): ?array
    {
        $source = get_option(self::OPTION);

        if (! is_array($source) || ! isset($source['company_id'])) {
            return null;
        }

        return [
            'company_id' => (int) $source['company_id'],
            'department_id' => isset($source['department_id']) ? (int) $source['department_id'] : null,
        ];
    }

    public function source(): ?Source
    {
        $saved = $this->get();

        return $saved === null ? null : new Source($saved['company_id'], $saved['department_id']);
    }

    /**
     * @return list<array{id: int, name: string}>
     *
     * @throws ApiException
     */
    public function companies(): array
    {
        $key = $this->companiesKey();
        $cached = get_transient($key);

        if (is_array($cached)) {
            return $cached;
        }

        $companies = $this->gateway()->companies();
        set_transient($key, $companies, self::TTL);

        return $companies;
    }

    /**
     * @return list<array{id: int, name: string}>
     *
     * @throws ApiException
     */
    public function departments(int $companyId): array
    {
        $key = $this->departmentsKey($companyId);
        $cached = get_transient($key);

        if (is_array($cached)) {
            return $cached;
        }

        $departments = $this->gateway()->departments($companyId);
        set_transient($key, $departments, self::TTL);

        return $departments;
    }

    /**
     * Stores the selection when it exists in the fetched lists.
     *
     * @throws ApiException
     */
    public function save(int $companyId, ?int $departmentId): bool
    {
        $companyIds = array_column($this->companies(), 'id');

        if (! in_array($companyId, $companyIds, true)) {
            return false;
        }

        if ($departmentId !== null) {
            $departmentIds = array_column($this->departments($companyId), 'id');

            if (! in_array($departmentId, $departmentIds, true)) {
                return false;
            }
        }

        update_option(self::OPTION, ['company_id' => $companyId, 'department_id' => $departmentId], false);

        return true;
    }

    public function refresh(): void
    {
        $cached = get_transient($this->companiesKey());

        foreach (is_array($cached) ? array_column($cached, 'id') : [] as $companyId) {
            delete_transient($this->departmentsKey((int) $companyId));
        }

        $saved = $this->get();

        if ($saved !== null) {
            delete_transient($this->departmentsKey($saved['company_id']));
        }

        delete_transient($this->companiesKey());
    }

    private function gateway(): Gateway
    {
        $token = $this->settings->token();

        return $this->gatewayFactory ? ($this->gatewayFactory)($token) : new Gateway($token);
    }

    private function companiesKey(): string
    {
        return 'yourstoryz_companies_'.$this->tokenHash();
    }

    private function departmentsKey(int $companyId): string
    {
        return 'yourstoryz_departments_'.$companyId.'_'.$this->tokenHash();
    }

    private function tokenHash(): string
    {
        return substr(md5($this->settings->token()), 0, 8);
    }
}
