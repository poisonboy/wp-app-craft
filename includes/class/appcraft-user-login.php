<?php

use Firebase\JWT\JWT;

function appcraft_login_user($request)
{
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $failed_attempts = get_transient('failed_login_attempts_' . $ip_address) ?: 0;

    if ($failed_attempts >= 5) {
        return new WP_REST_Response(array('status' => 'error', 'message' => __('Too many login attempts, please try again later.', 'app-craft')), 429);
    }
    

    $login_type = $request['login_type']; // 获取登录类型：'password' 或 'email_code'
    $username_or_email = $request['username'];
    $password = $request['password'];
    $email = $request['email'];
    $email_code = $request['email_code'];

    $user = username_exists($username_or_email) ? get_user_by('login', $username_or_email) : get_user_by('email', $username_or_email);

    if ($login_type === 'email_code') {
        $stored_code = get_transient("email_code_" . $email);
        if (!$stored_code) {
            return new WP_REST_Response(['status' => 'error', 'message' => __('Verification code has expired', 'app-craft')], 400);
        }
        if ($stored_code !== $email_code) {
            return new WP_REST_Response(['status' => 'error', 'message' => __('Incorrect verification code', 'app-craft')], 400);
        }
        // Clear the verification code
        delete_transient("email_code_" . $email);
        $user = get_user_by('email', $email);
    } else {
        if (!$user) {
            return new WP_REST_Response(array('status' => 'error', 'message' => __('Username or email address does not exist', 'app-craft')), 400);
        }
        // Validate password
        if (!wp_check_password($password, $user->data->user_pass, $user->ID)) {
            return new WP_REST_Response(array('status' => 'error', 'message' => __('Incorrect password', 'app-craft')), 400);
        }
    }


    // 如果到这里，登录验证成功
    delete_transient('failed_login_attempts_' . $ip_address);

    $expiration_hours = carbon_get_theme_option('appcraft_jwt_expiration') ?: 24;
    $key = carbon_get_theme_option('appcraft_jwt_secret_key');
    $payload = array(
        "user_id" => $user->ID,
        "exp" => time() + ($expiration_hours * 60 * 60)
    );

    $token = JWT::encode($payload, $key, 'HS256');
    $nonce = wp_create_nonce('create_comment');

    // 创建一个新的WP_REST_Request对象，设置必要的头部以携带token
    $profile_request = new WP_REST_Request();
    $profile_request->set_header('Authorization', $token);

    // 调用appcraft_get_user_profile函数以获取用户资料
    $profile_response = appcraft_get_user_profile($profile_request);

    // 检查appcraft_get_user_profile的响应是否有效
    if (is_wp_error($profile_response)) {
        return $profile_response;
    }

    // 从appcraft_get_user_profile的响应中提取用户资料
    $profile_data = $profile_response->get_data();

    // 合并token, nonce和用户资料到最终的响应中
    $response_data = array(
        "code" => "200",
        "message" => "success",
        "token" => $token,
        'nonce' => $nonce,
        "data" => array_merge(
            array(
                'id' => $user->ID,
                'userName' => $user->user_login,
                'nickname' => $user->nickname,
                'date' => $user->user_registered,
                'email' => $user->user_email,
                'roleId' => $user->roles[0],
                'roleName' => appcraft_translate_user_role($user->roles[0]),
            ),
            $profile_data['data']
        )
    );

    return new WP_REST_Response($response_data, 200);
}

// 角色翻译函数
function appcraft_translate_user_role($role)
{
    global $wp_roles;
    return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role]) : $role;
}
