<?php
// 发送邮箱验证码
function send_email_code($request)
{
    $email = sanitize_email($request['email']);
    $emailtype = $request['emailtype'];
    if (!is_email($email)) {
        return new WP_REST_Response(array('status' => 'error', 'message' => __('Incorrect email format', 'app-craft')), 400);
    }
    if ($emailtype !== 'login' && email_exists($email)) {
        return new WP_REST_Response(array('status' => 'error', 'message' => __('Email already registered', 'app-craft')), 400);
    }
    $email_code = rand(100000, 999999);
    set_transient("email_code_" . $email, $email_code, 5 * 60);

    $is_email_sent = wp_mail($email, __('Email Verification Code', 'app-craft'), __('Your verification code is:', 'app-craft') . ' ' . $email_code . '，' . __('valid for 5 minutes', 'app-craft'));

    if ($is_email_sent) {
        return new WP_REST_Response(array('status' => 'success', 'message' => __('Verification code sent', 'app-craft')), 200);
    } else {
        if (!function_exists('mail')) {
            return new WP_REST_Response(array('status' => 'error', 'message' => __('Server does not support email sending', 'app-craft')), 400);
        } else {
            return new WP_REST_Response(array('status' => 'error', 'message' => __('Failed to send verification code', 'app-craft')), 400);
        }
    }
}

// 生成复杂的随机ID
function generate_random_id($length = 10)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $randomIndex = mt_rand(0, strlen($chars) - 1);
        $result .= $chars[$randomIndex];
    }
    return $result;
}

// 公共的用户注册逻辑
function common_register_logic($username, $password, $email, $mobile, $nickname, $inviter_id)
{
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

        carbon_set_user_meta($user_id, 'appcraft_update_count', $update_count);
        carbon_set_user_meta($user_id, 'appcraft_upload_count', $upload_count);

        update_user_meta($user_id, 'is_vip', false);
        // 验证邀请人ID是否存在，并更新用户元数据
        if (!empty($inviter_id)) {
            $inviter_data = get_userdata($inviter_id);
            if ($inviter_data) {
                update_user_meta($user_id, 'inviter_id', $inviter_id);
            } else {
                // 邀请人ID不存在，返回错误信息
                return new WP_REST_Response(['status' => 'error', 'message' => __('Inviter ID does not exist', 'app-craft')], 400);
            }
        }


        $initial_points = carbon_get_theme_option('appcraft_initial_points') ?: 0;

        appcraft_manage_points($user_id, $initial_points, __('New user registration', 'app-craft'));

        // 给邀请人积分
        $inviter_id = get_user_meta($user_id, 'inviter_id', true);  // 从用户元数据中获取邀请人ID
        if (!empty($inviter_id)) {
            appcraft_give_inviter_points($user_id, $inviter_id);  // 给邀请人积分
        }

        $login_request = new WP_REST_Request();
        $login_request->set_param('username', $username);
        $login_request->set_param('password', $password);

        return appcraft_login_user($login_request);
    } else {
        return new WP_REST_Response(['status' => 'error', 'message' => $user_id->get_error_message()], 400);
    }
}

// 用户注册
function appcraft_register_user($request)
{
    $email_verify = carbon_get_theme_option('appcraft_email_verify');
    $username = sanitize_user($request['username']);
    $password = $request['password'];
    $email = sanitize_email($request['email']);
    $nickname = sanitize_text_field($request['nickname']);
    $code = $request['code'];
    $mobile = sanitize_text_field($request['mobile']);
    $inviter_id = sanitize_text_field($request['inviter_id']); 


    // 添加检查
    if (is_null($username) || is_null($password) || is_null($email) || is_null($nickname) || is_null($code)) {
        error_log("Received null values during registration.");
        return new WP_REST_Response(['status' => 'error', 'message' => __('Incomplete parameters', 'app-craft')], 400);
    } 
    if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Username length must be between 3-20 characters', 'app-craft')], 400);
    }
    if (preg_match('/[^\\w]/', $username)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Username contains illegal characters', 'app-craft')], 400);
    }
    if (username_exists($username)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Username already exists', 'app-craft')], 400);
    } 
    if (email_exists($email)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Email already registered', 'app-craft')], 400);
    }
    
    if (strlen($password) < 6) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Password must be at least 6 characters', 'app-craft')], 400);
    }
    
    if ($email_verify) {
        $stored_code = get_transient("email_code_" . $email);
        if (!$stored_code) {
            return new WP_REST_Response(['status' => 'error', 'message' => __('Verification code has expired', 'app-craft')], 400);
        }
        if ($stored_code !== $code) {
            return new WP_REST_Response(['status' => 'error', 'message' => __('Incorrect verification code', 'app-craft')], 400);
        }
        
        delete_transient("email_code_" . $email);
    }
    




    return common_register_logic($username, $password, $email, $nickname, $inviter_id, $mobile,);
}
// 一键注册
function one_click_register($request)
{
    $randomID = generate_random_id();
    $username = 'u_' . $randomID;
    $nickname = __('Unknown User', 'app-craft');  
    $password = wp_generate_password(12, true, true);
    $email = 'email_' . $randomID . '@weixin.com';
    $mobile = '';
    $inviter_id = sanitize_text_field($request['inviter_id']);

    return common_register_logic($username, $password, $email, $mobile, $nickname, $inviter_id);
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
    appcraft_manage_points($inviter_id, $points_for_invitation, sprintf(__('Invited new user with ID %s', 'app-craft'), $user_id));


    return true;
}
