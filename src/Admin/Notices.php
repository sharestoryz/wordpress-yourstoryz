<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

final class Notices
{
    private const TTL = 60;

    public function flash(string $type, string $message): void
    {
        set_transient($this->key(), ['type' => $type, 'message' => $message], self::TTL);
    }

    public function render(): void
    {
        $notice = get_transient($this->key());

        if (! is_array($notice) || ! isset($notice['type'], $notice['message'])) {
            return;
        }

        delete_transient($this->key());

        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr($notice['type'] === 'success' ? 'success' : 'error'),
            esc_html((string) $notice['message']),
        );
    }

    private function key(): string
    {
        return 'yourstoryz_notice_'.get_current_user_id();
    }
}
