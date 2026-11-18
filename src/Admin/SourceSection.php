<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

use YourStoryz\Api\ApiException;
use YourStoryz\Settings;
use YourStoryz\SourceSettings;

final class SourceSection
{
    public function __construct(
        private readonly Settings $settings,
        private readonly SourceSettings $source,
        private readonly AdminAction $adminAction,
    ) {}

    public function render(): void
    {
        echo '<h2>'.esc_html__('Bron', 'yourstoryz').'</h2>';

        if (! $this->settings->hasToken()) {
            echo '<p>'.esc_html__('Voer eerst een token in', 'yourstoryz').'</p>';

            return;
        }

        try {
            $companies = $this->source->companies();
        } catch (ApiException $exception) {
            $this->notice('error', $exception->userMessage());
            $this->refreshForm();

            return;
        }

        if ($companies === []) {
            $this->notice('warning', __('Dit account hoort bij geen company', 'yourstoryz'));
            $this->refreshForm();

            return;
        }

        $saved = $this->source->get();
        $companyIds = array_column($companies, 'id');

        if ($saved !== null && ! in_array($saved['company_id'], $companyIds, true)) {
            $this->notice('warning', __('De opgeslagen company is niet meer beschikbaar; kies een nieuwe.', 'yourstoryz'));
        }

        $requested = absint(wp_unslash($_GET['company'] ?? 0));
        $selected = match (true) {
            in_array($requested, $companyIds, true) => $requested,
            $saved !== null && in_array($saved['company_id'], $companyIds, true) => $saved['company_id'],
            count($companies) === 1 => $companies[0]['id'],
            default => 0,
        };

        $departments = [];

        if ($selected !== 0) {
            try {
                $departments = $this->source->departments($selected);
            } catch (ApiException $exception) {
                $this->notice('error', $exception->userMessage());
            }
        }

        $selectedDepartment = $saved !== null && $saved['company_id'] === $selected ? $saved['department_id'] : null;

        $this->adminAction->formOpen(SettingsPage::ACTION_SAVE_SOURCE);
        echo '<table class="form-table" role="presentation"><tr><th scope="row"><label for="yourstoryz_company_id">'
            .esc_html__('Company', 'yourstoryz').'</label></th><td>';
        echo '<select id="yourstoryz_company_id" name="yourstoryz_company_id">';

        if ($selected === 0) {
            echo '<option value="">'.esc_html__('Kies een company', 'yourstoryz').'</option>';
        }

        $this->options($companies, $selected);
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="yourstoryz_department_id">'.esc_html__('Department', 'yourstoryz').'</label></th><td>';
        echo '<select id="yourstoryz_department_id" name="yourstoryz_department_id">';
        echo '<option value="">'.esc_html__('Hele company', 'yourstoryz').'</option>';
        $this->options($departments, $selectedDepartment);
        echo '</select></td></tr></table>';
        submit_button(__('Bron opslaan', 'yourstoryz'));
        echo '</form>';

        $baseUrl = add_query_arg('page', Menu::SLUG, admin_url('admin.php'));
        printf(
            '<script>document.getElementById("yourstoryz_company_id").addEventListener("change",function(){if(this.value){window.location.href=%s+"&company="+encodeURIComponent(this.value);}});</script>',
            wp_json_encode($baseUrl, JSON_HEX_TAG | JSON_HEX_AMP),
        );

        $this->refreshForm();
    }

    /**
     * @param  list<array{id: int, name: string}>  $items
     */
    private function options(array $items, ?int $selected): void
    {
        foreach ($items as $item) {
            printf(
                '<option value="%d"%s>%s</option>',
                $item['id'],
                $item['id'] === $selected ? ' selected' : '',
                esc_html($item['name']),
            );
        }
    }

    private function notice(string $type, string $message): void
    {
        printf('<div class="notice notice-%s inline"><p>%s</p></div>', esc_attr($type), esc_html($message));
    }

    private function refreshForm(): void
    {
        $this->adminAction->formOpen(SettingsPage::ACTION_REFRESH);
        submit_button(__('Lijsten vernieuwen', 'yourstoryz'), 'secondary');
        echo '</form>';
    }
}
