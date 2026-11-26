<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

use YourStoryz\Api\ApiException;
use YourStoryz\Api\SourceStory;
use YourStoryz\Import\ImportResult;
use YourStoryz\Import\ImportRunner;
use YourStoryz\Source;
use YourStoryz\SourceSettings;
use YourStoryz\StoryCatalog;

final class PreviewPage
{
    public const SLUG = 'yourstoryz-preview';

    public const ACTION_REFRESH = 'yourstoryz_refresh_preview';

    public const ACTION_IMPORT = 'yourstoryz_import_now';

    private const MAX_MESSAGES = 5;

    public function __construct(
        private readonly SourceSettings $source,
        private readonly StoryCatalog $catalog,
        private readonly ImportRunner $runner,
        private readonly Notices $notices = new Notices,
        private readonly AdminAction $adminAction = new AdminAction,
    ) {}

    public function register(): void
    {
        add_action('admin_post_'.self::ACTION_REFRESH, [$this, 'handleRefresh']);
        add_action('admin_post_'.self::ACTION_IMPORT, [$this, 'handleImport']);
    }

    public function handleRefresh(): void
    {
        $this->adminAction->authorize(self::ACTION_REFRESH);

        $source = $this->source->source();

        if ($source instanceof Source) {
            $this->catalog->refresh($source);
            $this->notices->flash('success', __('Voorbeeld vernieuwd.', 'yourstoryz'));
        }

        $this->adminAction->redirect(self::SLUG);
    }

    public function handleImport(): void
    {
        $this->adminAction->authorize(self::ACTION_IMPORT);

        $outcome = $this->runner->run();
        $result = $outcome['result'];

        if ($result instanceof ImportResult) {
            $summary = sprintf(
                __('%1$d aangemaakt, %2$d overgeslagen, %3$d mislukt', 'yourstoryz'),
                $result->created,
                $result->skipped,
                $result->failed,
            );
            $messages = array_slice($result->messages, 0, self::MAX_MESSAGES);

            $this->notices->flash(
                $result->failed > 0 ? 'error' : 'success',
                implode(' · ', array_merge([$summary], $messages)),
            );
        } else {
            $this->notices->flash('error', match ($outcome['status']) {
                ImportRunner::STATUS_LOCKED => __('Import loopt al', 'yourstoryz'),
                ImportRunner::STATUS_NO_TOKEN => __('Er is geen token ingesteld; er is niets geïmporteerd.', 'yourstoryz'),
                ImportRunner::STATUS_NO_SOURCE => __('Er is nog geen bron gekozen; er is niets geïmporteerd.', 'yourstoryz'),
                default => sprintf(__('Import mislukt: %s', 'yourstoryz'), $outcome['message']),
            });
        }

        // The settings page posts here too and wants its user back on the settings page.
        $returnTo = ($_POST['yourstoryz_return'] ?? '') === Menu::SLUG ? Menu::SLUG : self::SLUG;
        $this->adminAction->redirect($returnTo);
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap"><h1>'.esc_html__('Voorbeeld', 'yourstoryz').'</h1>';
        $this->notices->render();

        $source = $this->source->source();

        if (! $source instanceof Source) {
            printf(
                '<p>%s <a href="%s">%s</a></p></div>',
                esc_html__('Er is nog geen bron gekozen.', 'yourstoryz'),
                esc_url(add_query_arg('page', Menu::SLUG, admin_url('admin.php'))),
                esc_html__('Kies een bron bij Instellingen', 'yourstoryz'),
            );

            return;
        }

        try {
            $result = $this->catalog->load($source);
        } catch (ApiException $exception) {
            printf('<div class="notice notice-error inline"><p>%s</p></div></div>', esc_html($exception->userMessage()));

            return;
        }

        printf(
            '<p>%s</p>',
            esc_html(sprintf(
                __('%1$d video-stories · %2$d media-stories overgeslagen', 'yourstoryz'),
                count($result['stories']),
                $result['skipped'],
            )),
        );

        $this->adminAction->formOpen(self::ACTION_REFRESH);
        submit_button(__('Vernieuwen', 'yourstoryz'), 'secondary');
        echo '</form>';

        $this->adminAction->formOpen(self::ACTION_IMPORT);
        submit_button(__('Nu importeren', 'yourstoryz'));
        echo '</form>';

        echo '<div style="overflow-x:auto"><table class="widefat striped"><thead><tr>';

        $headings = [
            '',
            __('Titel', 'yourstoryz'),
            __('Oriëntatie', 'yourstoryz'),
            __('Talen', 'yourstoryz'),
            __('Auteur', 'yourstoryz'),
            __('Bijgewerkt', 'yourstoryz'),
        ];

        foreach ($headings as $heading) {
            echo '<th>'.esc_html($heading).'</th>';
        }

        echo '</tr></thead><tbody>';

        foreach ($result['stories'] as $story) {
            $this->row($story);
        }

        echo '</tbody></table></div></div>';
    }

    private function row(SourceStory $story): void
    {
        $thumbnail = $story->video->thumbnailUrl === ''
            ? ''
            : sprintf(
                '<img src="%s" alt="" width="64" loading="lazy">',
                esc_url($story->video->thumbnailUrl),
            );

        $languages = implode(', ', array_map(
            fn ($content): string => $content->lang.'·'.$content->type,
            $story->video->contents,
        ));

        $orientation = match ($story->video->orientation) {
            'horizontal' => __('horizontaal', 'yourstoryz'),
            'vertical' => __('verticaal', 'yourstoryz'),
            null => __('onbekend', 'yourstoryz'),
            default => $story->video->orientation,
        };

        $timestamp = strtotime($story->updatedAt);

        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $thumbnail,
            esc_html($story->title),
            esc_html($orientation),
            esc_html($languages === '' ? __('geen teksten', 'yourstoryz') : $languages),
            esc_html($story->author ?? __('onbekend', 'yourstoryz')),
            esc_html($timestamp ? wp_date('d-m-Y H:i', $timestamp) : $story->updatedAt),
        );
    }
}
