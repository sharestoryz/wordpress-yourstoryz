<?php

declare(strict_types=1);

namespace YourStoryz\Admin;

final class AdminAction
{
    public function authorize(string $action): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Je hebt geen toegang tot deze pagina.', 'yourstoryz'), '', ['response' => 403]);
        }

        check_admin_referer($action);
    }

    public function redirect(string $page): never
    {
        wp_safe_redirect(add_query_arg('page', $page, admin_url('admin.php')));
        exit;
    }

    public function formOpen(string $action): void
    {
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="'.esc_attr($action).'">';
        wp_nonce_field($action);
    }
}
