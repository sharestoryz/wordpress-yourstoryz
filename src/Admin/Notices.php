<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

final class Notices
{
    private const TTL = 60;

    public function flash(string $type, string $message): void
    {
        $notices = get_transient($this->key());
        $notices = is_array($notices) ? $notices : [];
        $notices[] = ['type' => $type, 'message' => $message];

        set_transient($this->key(), $notices, self::TTL);
    }

    public function render(): void
    {
        $notices = get_transient($this->key());

        if (! is_array($notices)) {
            return;
        }

        delete_transient($this->key());

        foreach ($notices as $notice) {
            if (! isset($notice['type'], $notice['message'])) {
                continue;
            }

            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type'] === 'success' ? 'success' : 'error'),
                esc_html((string) $notice['message']),
            );
        }
    }

    private function key(): string
    {
        return 'yourstoryz_notice_'.get_current_user_id();
    }
}
