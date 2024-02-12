<?php
/*
Plugin Name: Imgur Image Hosting
Description: Host your WordPress images on Imgur.
Version: 1.0
Author: Your Name
*/

function imgur_enqueue_media_library_scripts() {
    // Use get_current_screen() to check if you're on the Media Library page or any other specific admin page
    $screen = get_current_screen();
    if ($screen->base === 'upload') {
        // Enqueue your script here
        wp_enqueue_script('imgur-ajax-upload', plugin_dir_url(__FILE__) . 'js/ajax-imgur.js', array('jquery'), '1.0', true);

        // Localize script to pass Ajax URL (and potentially other variables) to the JavaScript file
        wp_localize_script('imgur-ajax-upload', 'imgurAjax', array('ajaxUrl' => admin_url('admin-ajax.php')));
    }
}
add_action('admin_enqueue_scripts', 'imgur_enqueue_media_library_scripts');


add_action('admin_init', 'imgur_image_hosting_settings');

function imgur_image_hosting_settings() {
    register_setting('media', 'imgur_client_id');
}

add_action('admin_menu', 'imgur_image_hosting_menu');

function imgur_image_hosting_menu() {
    add_options_page('Imgur Image Hosting Settings', 'Imgur Hosting', 'manage_options', 'imgur-image-hosting', 'imgur_image_hosting_options_page');
}

function imgur_image_hosting_options_page() {
    ?>
    <div class="wrap">
        <h2>Imgur Image Hosting</h2>
        <form action="options.php" method="post">
            <?php settings_fields('media'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="imgur_client_id">Imgur Client ID</label></th>
                    <td><input name="imgur_client_id" id="imgur_client_id" type="text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function imgur_custom_media_button() {
    echo '<a href="#" id="imgur-upload-button" class="button">Upload to Imgur</a>';
    // Include a file input for selecting images
    echo '<input type="file" id="imgur-file-input" style="display: none;" />';
}
add_action('media_buttons', 'imgur_custom_media_button');



function imgur_handle_ajax_upload() {
    check_ajax_referer('imgur_secure_upload', 'security');

    $client_id = get_option('imgur_client_id');
    $file = $_FILES['image'];
    
    $response = wp_remote_post('https://api.imgur.com/3/image', [
        'headers' => ['Authorization' => 'Client-ID ' . $client_id],
        'body' => ['image' => base64_encode(file_get_contents($file['tmp_name']))],
        'timeout' => 60
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Failed to upload to Imgur']);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['data']['link'])) {
        // Create a placeholder in the WP Media Library
        $attachment_id = imgur_create_placeholder_attachment($body['data']['link']);
        wp_send_json_success(['url' => $body['data']['link'], 'attachment_id' => $attachment_id]);
    } else {
        wp_send_json_error(['message' => 'Failed to retrieve Imgur URL']);
    }
}
add_action('wp_ajax_imgur_image_upload', 'imgur_handle_ajax_upload');

function imgur_create_placeholder_attachment($imgur_url) {
    $attachment = [
        'post_mime_type' => 'image/jpeg', // Adjust based on actual image type
        'post_title'     => sanitize_file_name(basename($imgur_url)),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'guid'           => $imgur_url
    ];

    $attach_id = wp_insert_attachment($attachment, $imgur_url);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $imgur_url);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}

function imgur_image_hosting_activate() {
    // Activation code here, if necessary
}

function imgur_image_hosting_deactivate() {
    // Deactivation code here, if necessary
}

register_activation_hook(__FILE__, 'imgur_image_hosting_activate');
register_deactivation_hook(__FILE__, 'imgur_image_hosting_deactivate');
?>