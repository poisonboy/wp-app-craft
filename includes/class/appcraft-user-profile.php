<?php
defined('ABSPATH') or die('Direct file access not allowed');





function appcraft_get_user_profile(WP_REST_Request $request)
{
    $user_id = appcraft_verify_user_token($request);
    $today = date('Y-m-d');
    $last_signin_date = get_user_meta($user_id, 'last_signin_date', true);

    $consecutive_days = get_user_meta($user_id, 'consecutive_days', true) ?: 0;
    $total_points = get_user_meta($user_id, 'appcraft_user_points', true) ?: 0;
    $today_earned_points = get_user_meta($user_id, 'today_earned_points', true) ?: 0;


    $isSignedIn = ($last_signin_date === $today);


    $user = get_userdata($user_id);

    // 检查 $user 是否为有效的 WP_User 对象
    if (!$user instanceof WP_User) {
        // 如果不是，返回错误信息
        return new WP_REST_Response(array('status' => 'error', 'message' => __('Invalid user ID', 'wp-app-craft')), 400);
    }
    $profile = array(
        // "token" => $token,
        "id" => $user->ID,
        "userName" => $user->user_login,
        "nickname" => $user->nickname,
        "date" => $user->user_registered,
        "email" => $user->user_email,
        "mobile" => get_user_meta($user->ID, 'mobile', true),
        "roleId" => $user->roles[0],
        "roleName" => appcraft_translate_user_role($user->roles[0]),
        "avatar" => get_user_meta($user->ID, '_appcraft_avatar', true),
        "bio" => get_user_meta($user->ID, '_appcraft_bio', true),
        "update_count" => carbon_get_user_meta($user->ID, 'appcraft_update_count'),
        "upload_count" => carbon_get_user_meta($user->ID, 'appcraft_upload_count'),
        "updatephone_count" => carbon_get_user_meta($user->ID, 'appcraft_phone_update_count'),
        "inviter_count" => get_user_meta($user->ID, 'appcraft_inviter_count', true),
        'consecutive_days' => $consecutive_days,
        'total_points' => $total_points,
        'today_earned_points' => $today_earned_points,
        'isSignedIn' => $isSignedIn
    );

    return new WP_REST_Response(array('status' => 'success', 'data' => $profile), 200);
}

function appcraft_update_user_profile(WP_REST_Request $request)
{
    $user_id = appcraft_verify_user_token($request);

    $update_count = carbon_get_user_meta($user_id, 'appcraft_update_count');
    if ($update_count-- <= 0) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Update limit reached', 'wp-app-craft')], 403);
    }
    carbon_set_user_meta($user_id, 'appcraft_update_count', $update_count);

    foreach (['nickname', 'bio', 'avatar'] as $key) {
        if ($value = $request->get_param($key)) {
            if ($key === 'nickname') {
                wp_update_user([
                    'ID'           => $user_id,
                    'nickname'     => $value,
                    'display_name' => $value
                ]);
            } else {
                update_user_meta($user_id, '_appcraft_' . $key, $value);
            }
        }
    }


    if ($email = sanitize_email($request['email'])) {
        $code = $request['code'];
        $stored_code = get_transient("email_code_" . $email);
        // 检查新邮箱是否已经被其他用户注册
        if (email_exists($email) && email_exists($email) != $user_id) {
            return new WP_REST_Response(['status' => 'error', 'message' => __('Email already registered', 'wp-app-craft')], 400);
        }
        if (is_null($code) || !$stored_code || $stored_code !== $code) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => is_null($code) ? __('Missing email verification code', 'wp-app-craft') : (!$stored_code ? __('Verification code has expired', 'wp-app-craft') : __('Incorrect verification code', 'wp-app-craft')),
                400
            ]);
        }
        delete_transient("email_code_" . $email);
        wp_update_user(['ID' => $user_id, 'user_email' => $email]);
    }
    $has_given_initial_points = get_user_meta($user_id, 'has_given_initial_points', true);
    $has_given_invitation_points = get_user_meta($user_id, 'has_given_invitation_points', true);

    error_log('User ID: ' . $user_id . ' - Initial Points Given: ' . var_export($has_given_initial_points, true));
    error_log('User ID: ' . $user_id . ' - Invitation Points Given: ' . var_export($has_given_invitation_points, true));

    if (!$has_given_initial_points) {
        $initial_points = carbon_get_theme_option('appcraft_initial_points') ?: 0;

        appcraft_manage_points($user_id, $initial_points, __('New user registration', 'wp-app-craft'), 'add', AC_TYPE_REGISTRATION);
        update_user_meta($user_id, 'has_given_initial_points', true);
    }

    if (!$has_given_invitation_points) {

        // 获取邀请人ID
        $inviter_id = get_user_meta($user_id, 'inviter_id', true);
        error_log('获取邀请人ID' . $inviter_id);
        if (!empty($inviter_id)) {
            // 赠送邀请积分
            appcraft_give_inviter_points($user_id, $inviter_id);
            // 标记为已赠送
            update_user_meta($user_id, 'has_given_invitation_points', true);
        }
    }
    $updated_profile_response = appcraft_get_user_profile($request);
    $response_data = $updated_profile_response->get_data();
    $response_data['message'] = __('Profile updated successfully', 'wp-app-craft');

    return new WP_REST_Response($response_data, 200);
}
// 给邀请人积分
function appcraft_give_inviter_points($user_id, $inviter_id)
{
    $points_for_invitation = carbon_get_theme_option('appcraft_points_for_invitation');


    // 更新邀请人的邀请数量
    $current_inviter_count = get_user_meta($inviter_id, 'appcraft_inviter_count', true) ?: 0;
    $new_inviter_count = $current_inviter_count + 1;
    update_user_meta($inviter_id, 'appcraft_inviter_count', $new_inviter_count);
    // 使用通用函数来添加积分
    appcraft_manage_points($inviter_id, $points_for_invitation, sprintf(__('Invited new user with ID %s', 'wp-app-craft'), $user_id), 'add', AC_TYPE_INVITATION);


    return true;
}
