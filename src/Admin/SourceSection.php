<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

use YourStoryz\Api\ApiException;
use YourStoryz\SourceSettings;

/**
 * Resolves what the "Bron" step shows: the lists to choose from, the current choice and a problem to report.
 */
final class SourceSection
{
    public function __construct(
        private readonly SourceSettings $source,
    ) {}

    /**
     * @return array{
     *     companies: list<array{id: int, name: string}>,
     *     departments: list<array{id: int, name: string}>,
     *     company: int,
     *     department: int|null,
     *     notice: array{type: string, message: string}|null,
     * }
     */
    public function view(): array
    {
        $view = ['companies' => [], 'departments' => [], 'company' => 0, 'department' => null, 'notice' => null];

        try {
            $companies = $this->source->companies();
        } catch (ApiException $exception) {
            return ['notice' => ['type' => 'error', 'message' => $exception->userMessage()]] + $view;
        }

        if ($companies === []) {
            return ['notice' => ['type' => 'warning', 'message' => __('Dit account hoort bij geen company', 'yourstoryz')]] + $view;
        }

        $saved = $this->source->get();
        $companyIds = array_column($companies, 'id');
        $savedIsAvailable = $saved !== null && in_array($saved['company_id'], $companyIds, true);

        $view['companies'] = $companies;
        $view['company'] = match (true) {
            $savedIsAvailable => $saved['company_id'],
            count($companies) === 1 => $companies[0]['id'],
            default => 0,
        };
        $view['department'] = $savedIsAvailable ? $saved['department_id'] : null;

        if ($saved !== null && ! $savedIsAvailable) {
            $view['notice'] = ['type' => 'warning', 'message' => __('De opgeslagen company is niet meer beschikbaar; kies een nieuwe.', 'yourstoryz')];
        }

        if ($view['company'] !== 0) {
            try {
                $view['departments'] = $this->source->departments($view['company']);
            } catch (ApiException $exception) {
                $view['notice'] = ['type' => 'error', 'message' => $exception->userMessage()];
            }
        }

        return $view;
    }
}
