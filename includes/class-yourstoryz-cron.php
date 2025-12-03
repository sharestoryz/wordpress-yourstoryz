<?php

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

use YourStoryz\PhpSdk\YourStoryz as YourStoryzConnector;

class YourStoryz_Cron
{
    public function __construct()
    {
        // Hook the execution function to the scheduled action
        add_action(YOURSTORYZ_CRON_HOOK, [$this, 'fetch_api_data']);

        add_filter('image_sideload_extensions', fn ($allowed_extensions, $file) => array_merge($allowed_extensions, ['mp4']), 10, 2);
    }

    /**
     * Schedules the hourly cron job.
     */
    public function schedule_event()
    {
        if (! wp_next_scheduled(YOURSTORYZ_CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', YOURSTORYZ_CRON_HOOK);
        }
    }

    /**
     * Clears the scheduled cron job.
     */
    public function clear_event()
    {
        $timestamp = wp_next_scheduled(YOURSTORYZ_CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, YOURSTORYZ_CRON_HOOK);
        }
    }

    /**
     * The function that runs hourly to fetch or update data via the API.
     */
    public function fetch_api_data()
    {
        if (! function_exists('media_handle_sideload')) {
            require_once ABSPATH.'wp-admin/includes/image.php';
            require_once ABSPATH.'wp-admin/includes/file.php';
            require_once ABSPATH.'wp-admin/includes/media.php';
        }

        // Get the public functions for easy access to settings
        $api_token = get_yourstoryz_api_token();
        $storiable_type = get_yourstoryz_storiable_type();
        $storiable_id = get_yourstoryz_storiable_id();

        if (empty($api_token) || empty($storiable_id)) {
            error_log('YourStoryz Cron Error: Missing API Token or Storiable ID. Data fetch skipped.');

            return;
        }

        $connector = new YourStoryzConnector(
            $api_token,
            YOURSTORYZ_API_URL
        );

        $stories = match ($storiable_type) {
            'Company' => $connector->companies()->stories($storiable_id),
            'Department' => $connector->departments()->stories($storiable_id),
        };

        $stories->collect()->each(function ($story) {
            $query = new WP_Query([
                'post_status' => 'draft',
                'meta_query' => [
                    [
                        'key' => 'reference_id',
                        'value' => $story['id'],
                    ],
                ],
            ]);

            if ($query->post_count > 0) {
                return;
            }

            $post_id = wp_insert_post([
                'post_author' => get_current_user_id(),
                'post_title' => $story['title'],
                'post_date' => $story['created_at'],
                'meta_input' => [
                    'reference_id' => $story['id'],
                ],
            ]);

            $thumbnail_id = media_sideload_image(
                $story['video']['thumbnail_url'],
                $post_id,
                null,
                'id'
            );

            set_post_thumbnail($post_id, $thumbnail_id);

            $video_id = media_sideload_image(
                $story['video']['video_url'],
                $post_id,
                null,
                'id'
            );

            $post_content = '';

            if (isset($story['video']['contents'])) {
                $contents = array_filter(
                    $story['video']['contents'],
                    fn ($content) => $content['lang'] === Locale::getPrimaryLanguage(get_bloginfo('language'))
                );

                $content = array_find($contents, fn (array $content) => $content['type'] === 'news') ?? array_first($contents);
                $parsedown = new Parsedown;
                $post_content .= $parsedown->text($content['content']);
            }

            $post_content .= '
                <!-- wp:video -->
                <figure class="wp-block-video">
                    <video controls src="'.esc_url(wp_get_attachment_url($video_id)).'"></video>
                </figure>
                <!-- /wp:video -->
            ';

            wp_update_post([
                'ID' => $post_id,
                'post_content' => $post_content,
            ]);
        });
    }
}
