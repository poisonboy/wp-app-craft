<?php
defined('ABSPATH') or die('Direct file access not allowed');




// 公共的用户注册逻辑
function appcraft_common_register_logic($username, $password, $email, $nickname, $inviter_id, $mobile, $openid)
{
    // error_log('传递过来的$inviter_id' . $inviter_id);
    $user_data = array(
        'user_login' => $username,
        'user_pass' => $password,
        'user_email' => $email,
        'nickname' => $nickname,
        'mobile' => $mobile,
    );
    $user_id = wp_insert_user($user_data);


    if (!is_wp_error($user_id)) {
        $update_count = carbon_get_theme_option('appcraft_updateprofile_times');
        $upload_count = carbon_get_theme_option('appcraft_uploadavatar_times');
        $update_phone_count = carbon_get_theme_option('appcraft_updatephone_times');
        carbon_set_user_meta($user_id, 'appcraft_update_count', $update_count);
        carbon_set_user_meta($user_id, 'appcraft_upload_count', $upload_count);
        carbon_set_user_meta($user_id, 'appcraft_phone_update_count', $update_phone_count);
        update_user_meta($user_id, 'mobile', $mobile);
        update_user_meta($user_id, 'openid', $openid);
        update_user_meta($user_id, 'is_vip', false);
        // 设置初始积分标志
        update_user_meta($user_id, 'has_given_initial_points', false);
        // 设置邀请积分标志
        update_user_meta($user_id, 'has_given_invitation_points', false);
        // 验证邀请人ID是否存在，并更新用户元数据
        if (!empty($inviter_id)) {
            $inviter_data = get_userdata($inviter_id);
            if ($inviter_data) {
                update_user_meta($user_id, 'inviter_id', $inviter_id);
            } else {
                // 邀请人ID不存在，返回错误信息
                return new WP_REST_Response(['status' => 'error', 'message' => __('Inviter ID does not exist', 'wp-app-craft')], 400);
            }
        }



        // // 给邀请人积分
        $inviter_id = get_user_meta($user_id, 'inviter_id', true);  // 从用户元数据中获取邀请人ID
        // error_log('注册后的$inviter_id' . $inviter_id);
        // if (!empty($inviter_id)) {
        //     appcraft_give_inviter_points($user_id, $inviter_id);  // 给邀请人积分
        // }
        // 获取默认头像 URL
        $default_avatar = carbon_get_theme_option('appcraft_app_default_avatar');
        // error_log($default_avatar);
        if (!$default_avatar) {
            // 如果没有设置默认头像，则使用插件目录下的默认头像
            $default_avatar = plugin_dir_url(__FILE__) . 'assets/images/avatar/default_avatar.jpg'; // 路径根据实际情况调整
        }
        update_user_meta($user_id, '_appcraft_avatar', $default_avatar);

        $login_request = new WP_REST_Request();
        $login_request->set_param('username', $username);
        $login_request->set_param('password', $password);

        return appcraft_login_user($login_request);
    } else {
        return new WP_REST_Response(['status' => 'error', 'message' => $user_id->get_error_message()], 400);
    }
}
// 一键注册
function appcraft_one_click_register($request)
{
    $enable_one_click_registration = carbon_get_theme_option('enable_one_click_registration');
    if (!$enable_one_click_registration) {
        return new WP_REST_Response([
            'status' => 'error',
            'message' => __('One-click registration is disabled', 'wp-app-craft')
        ], 400);
    }

    $randomID = appcraft_generate_random_id();
    $username = 'u_' . $randomID;
    $nickname = __('Unknown User', 'wp-app-craft');
    $password = wp_generate_password(12, true, true);
    $email = 'email_' . $randomID . '@weixin.com';

    // 使用从请求中获取的手机号码，如果没有提供，则设为空
    $mobile = $request->get_param('mobile') ? sanitize_text_field($request->get_param('mobile')) : '';
    $openid = $request->get_param('openid') ? sanitize_text_field($request->get_param('openid')) : '';

    $inviter_id = sanitize_text_field($request['inviter_id']);

    return appcraft_common_register_logic($username, $password, $email, $mobile, $nickname, $inviter_id, $openid);
}


// 用户注册
function appcraft_register_user($request)
{
    $userData = $request->get_param('userData');
    if (!$userData) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('userData not provided', 'wp-app-craft')], 400);
    }

    $required_fields = ['username', 'password', 'email',  'code'];
    foreach ($required_fields as $field) {
        if (empty($userData[$field])) {
            return new WP_REST_Response(['status' => 'error', 'message' => sprintf(__('Missing required field: %s', 'wp-app-craft'), $field)], 400);
        }
    }

    $username = sanitize_user($userData['username']);
    $password = $userData['password'];
    $email = sanitize_email($userData['email']);
    $nickname = sanitize_text_field($userData['nickname']);
    $code = $userData['code'];
    $mobile = isset($userData['mobile']) ? sanitize_text_field($userData['mobile']) : '';
    $openid = isset($userData['openid']) ? sanitize_text_field($userData['openid']) : '';
    $inviter_id = isset($userData['inviter_id']) ? sanitize_text_field($userData['inviter_id']) : '';

    if (preg_match('/[^\\w]/', $username)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Username contains illegal characters', 'wp-app-craft')], 400);
    }
    if (username_exists($username)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Username already exists', 'wp-app-craft')], 400);
    }
    // 检查邮箱是否已经被注册
    if (email_exists($email)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Email already registered', 'wp-app-craft')], 400);
    }


    if (strlen($password) < 6) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Password must be at least 6 characters', 'wp-app-craft')], 400);
    }

    $email_verify = carbon_get_theme_option('appcraft_email_verify');
    if ($email_verify) {
        $stored_code = get_transient("email_code_" . $email);
        if (!$stored_code) {
            return new WP_REST_Response(['status' => 'error', 'message' => __('Verification code has expired', 'wp-app-craft')], 400);
        }
        if ($stored_code !== $code) {
            return new WP_REST_Response(['status' => 'error', 'message' => __('Incorrect verification code', 'wp-app-craft')], 400);
        }

        delete_transient("email_code_" . $email);
    }

    return appcraft_common_register_logic($username, $password, $email, $nickname, $inviter_id, $mobile, $openid);
}
