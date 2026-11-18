<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

use YourStoryz\Api\ApiException;
use YourStoryz\Api\SourceStory;
use YourStoryz\Source;
use YourStoryz\SourceSettings;
use YourStoryz\StoryCatalog;

final class PreviewPage
{
    public const SLUG = 'yourstoryz-preview';

    public const ACTION_REFRESH = 'yourstoryz_refresh_preview';

    public function __construct(
        private readonly SourceSettings $source,
        private readonly StoryCatalog $catalog,
        private readonly Notices $notices = new Notices,
    ) {}

    public function register(): void
    {
        add_action('admin_post_'.self::ACTION_REFRESH, [$this, 'handleRefresh']);
    }

    public function handleRefresh(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'yourstoryz'), '', ['response' => 403]);
        }

        check_admin_referer(self::ACTION_REFRESH);

        $source = $this->source->source();

        if ($source instanceof Source) {
            $this->catalog->refresh($source);
            $this->notices->flash('success', 'Voorbeeld vernieuwd.');
        }

        wp_safe_redirect(add_query_arg('page', self::SLUG, admin_url('admin.php')));
        exit;
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
            esc_html(sprintf('%d video-stories · %d media-stories overgeslagen', count($result['stories']), $result['skipped'])),
        );

        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="'.esc_attr(self::ACTION_REFRESH).'">';
        wp_nonce_field(self::ACTION_REFRESH);
        submit_button(__('Vernieuwen', 'yourstoryz'), 'secondary');
        echo '</form>';

        echo '<div style="overflow-x:auto"><table class="widefat striped"><thead><tr>';

        foreach (['', 'Titel', 'Oriëntatie', 'Talen', 'Auteur', 'Bijgewerkt'] as $heading) {
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
            'horizontal' => 'horizontaal',
            'vertical' => 'verticaal',
            null => 'onbekend',
            default => $story->video->orientation,
        };

        $timestamp = strtotime($story->updatedAt);

        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $thumbnail,
            esc_html($story->title),
            esc_html($orientation),
            esc_html($languages === '' ? 'geen teksten' : $languages),
            esc_html($story->author ?? 'onbekend'),
            esc_html($timestamp ? wp_date('d-m-Y H:i', $timestamp) : $story->updatedAt),
        );
    }
}
