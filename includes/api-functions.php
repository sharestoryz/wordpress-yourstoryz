<?php
// Connect to api and create posts with api data
function call_api() {
    $api_url = 'https://dashboard.yourstoryz.com/api/v1/videos/'; 
    $token = 'Bearer 3453|wSA86D0bogdkirWlXKaFtrjBR9KRPsfSckeaNfhdf9667aa9'; 
    $autoplay = get_option('yourstoryz_plugin_autoplay') ? 'autoplay' : '';

    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => $token,
            'Content-Type'  => 'application/json',
        ),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if ($data === null) {
            wp_send_json_error('Error decoding JSON response');
        } else {
            foreach ($data as $api_data) {
                // if (!post_exists($api_data->id)) { // check for duplicates based on title
                if (get_post('99' . $api_data->id)) { // check for duplicates based on id
                } else {
                    $create_post = array(
                        'post_author'           => 'default',
                        'post_content'          => '
                        <!-- wp:heading {"level":1} -->
                        <h1 class="wp-block-heading">' . $api_data->title . '</h1>
                        <!-- /wp:heading -->
                        
                        <!-- wp:heading {"level":3} -->
                        <h3 class="wp-block-heading">made by: "dit moet nog in de api"</h3>
                        <!-- /wp:heading -->
                        
                        <!-- wp:heading {"level":3} -->
                        <h3 class="wp-block-heading">' . $api_data->contents[0]->title . '</h3>
                        <!-- /wp:heading -->
                        
                        <!-- wp:paragraph -->
                        <p>'. $api_data->contents[0]->content . '</p>
                        <!-- /wp:paragraph -->
                        
                        <!-- wp:video -->
                        <figure class="wp-block-video"><video controls ' . $autoplay . ' src="' . $api_data->video_url . '"></video></figure>
                        <!-- /wp:video -->
                        ',
                        'post_content_filtered' => '',
                        'post_title'            => $api_data->title,
                        'post_excerpt'          => '',
                        'post_status'           => 'draft',
                        'post_type'             => 'post',
                        'post_date'             => '',
                        'post_date_gmt'         => '',
                        'tags_input'            => $api_data->contents[0]->lang,
                        'import_id'             => '99' . $api_data->id,
                    );
                    wp_insert_post($create_post);
                    // $post_id = wp_insert_post($create_post);
                    // if (is_wp_error($post_id)) {
                    //     wp_send_json_error('Error creating post: ' . $post_id->get_error_message());
                    // }
                    //   wp_send_json_success(array('api_data' => $data));
                }
            }
        }
    }
}
add_action('wp_ajax_call_api', 'call_api'); // Fires ajax action if user is logged in.
?>