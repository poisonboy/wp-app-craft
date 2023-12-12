<?php
defined('ABSPATH') or die('Direct file access not allowed');





// 修改密码
function appcraft_update_password(WP_REST_Request $request)
{
    $user_id = appcraft_verify_user_token($request);
    $new_password = $request->get_param('password');

    // Validate password length
    if (strlen($new_password) < 8) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Password must be at least 8 characters long', 'wp-app-craft')], 400);
    }

    // Validate password complexity (must contain at least one letter and one number)
    if (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Password must contain at least one letter and one number', 'wp-app-craft')], 400);
    }

    // Update password
    $update_status = wp_set_password($new_password, $user_id);
    if ($update_status === false) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Password update failed', 'wp-app-craft')], 500);
    }

    return new WP_REST_Response(['status' => 'success', 'message' => __('Password updated', 'wp-app-craft')], 200);
}






// 注销账号（发送确认邮件）
function appcraft_send_delete_confirmation_email(WP_REST_Request $request)
{
    $user_id = appcraft_verify_user_token($request);
    $email_code = $request['email_code'];
    $email = get_the_author_meta('user_email', $user_id);
    $timestamp = time();
    $action = 'confirm_delete';
    $stored_code = get_transient("email_code_" . $email);

    if (!$stored_code) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Verification code has expired', 'wp-app-craft')], 400);
    }
    if ($stored_code !== $email_code) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Incorrect verification code', 'wp-app-craft')], 400);
    }

    // Clear verification code
    delete_transient("email_code_" . $email);

    // Create a salted token
    $salt = wp_generate_password(20); // Generate a random string of 20 characters as salt
    $token = wp_hash($user_id . '|' . $timestamp . '|' . $action . '|' . $salt);

    // Store timestamp and salt in transient data
    set_transient("delete_user_" . $user_id, ['timestamp' => $timestamp, 'salt' => $salt], 15 * 60); // Valid for 15 minutes

    // Create API link
    $api_url = rest_url('wp-app-craft/v1/confirm-delete');
    $confirmation_link = add_query_arg(['user_id' => $user_id, 'timestamp' => $timestamp, 'token' => $token], $api_url);

    // Get the timestamp of the last email sent
    $last_sent = get_transient("email_last_sent_" . $user_id);

    // If less than 15 minutes have passed since the last email, do not send an email
    if ($last_sent && (time() - $last_sent < 900)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Please wait 15 minutes before trying again', 'wp-app-craft')], 429);
    }

    // Send confirmation email
    $email = get_the_author_meta('user_email', $user_id);
    $message = __("Please reply using the email associated with your account, specifying the UID of the account to be deleted and the reason. We will lock your account upon receiving the email and permanently delete all information after one week. \n\nNote: This action is irreversible. If you did not request account deletion, please ignore this email.", 'wp-app-craft');
    $is_email_sent = wp_mail($email, __('Confirm Account Deletion', 'wp-app-craft'), $message);

    // Respond based on the status of the email sent
    if ($is_email_sent) {
        set_transient("email_last_sent_" . $user_id, time(), 300);
        return new WP_REST_Response(['status' => 'success', 'message' => __('Confirmation email sent, please check your inbox.', 'wp-app-craft')], 200);
    } else {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Failed to send email, please try again later.', 'wp-app-craft')], 500);
    }
}


// 确认并删除账号
function appcraft_confirm_and_delete_account(WP_REST_Request $request)
{
    $user_id = intval($request->get_param('user_id'));
    $timestamp = intval($request->get_param('timestamp'));
    $token = sanitize_text_field($request->get_param('token'));
    $action = 'confirm_delete';

    $expected_token = wp_hash($user_id . '|' . $timestamp . '|' . $action);

    // 验证 token 和时间戳
    if ($expected_token !== $token || time() - $timestamp > 3600) {
        // Token 无效或过期
        return new WP_REST_Response(['status' => 'error', 'message' => __('Invalid or expired token', 'wp-app-craft')], 400);
    }

    // 执行删除操作
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    wp_delete_user($user_id);

    return new WP_REST_Response(['status' => 'success', 'message' => __('Account deleted', 'wp-app-craft')], 200);
}
