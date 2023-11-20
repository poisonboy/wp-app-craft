<?php

function appcraft_upload_user_avatar(WP_REST_Request $request)
{

  $user_id = verify_user_token($request);
  $upload_count = carbon_get_user_meta($user_id, 'appcraft_upload_count');

  if ($upload_count <= 0) {
    return new WP_REST_Response(['status' => 'error', 'message' => __('Avatar upload limit reached', 'wp-app-craft')], 403);
  }
  $upload_count--;
  carbon_set_user_meta($user_id, 'appcraft_upload_count', $upload_count);

  if (!isset($_FILES['avatar'])) {
    return wp_send_json_error(__('Avatar not provided', 'wp-app-craft'));
  }

  $avatar = $_FILES['avatar'];

  $file_type = mime_content_type($avatar['tmp_name']);
  $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
  if (!in_array($file_type, $allowed_types)) {
    return wp_send_json_error(__('File type not allowed', 'wp-app-craft'));
  }

  if ($avatar['size'] > 2 * 1024 * 1024) {
    return wp_send_json_error(__('File size exceeds limit', 'wp-app-craft'));
  }

  require_once(ABSPATH . 'wp-admin/includes/file.php');


  $upload_overrides = array('test_form' => false);
  $movefile = wp_handle_upload($avatar, $upload_overrides);

  if ($movefile && !isset($movefile['error'])) {
    $response_data = array(
      "code" => "200",
      "message" => __("Avatar uploaded successfully", 'wp-app-craft'),


      "data" => array(
        "url" => $movefile['url'],

      )
    );




    return new WP_REST_Response($response_data, 200);
  } else {
    return wp_send_json_error($movefile['error']);
  }
}
function validateAndGetAvatar($data)
{
  if (!isset($data['avatar'])) {
    return wp_send_json_error(__('Avatar not provided', 'wp-app-craft'));
  }

  $avatar = $data['avatar'];
  if (!isset($avatar['name'], $avatar['type'])) {
    return wp_send_json_error(__('Incorrect parameters', 'wp-app-craft'));
  }

  return [
    'name' => $avatar['name'],
    'type' => $avatar['type'],
    'file' => $avatar['file'],
  ];
}
