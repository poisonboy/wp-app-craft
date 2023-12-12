<?php
defined('ABSPATH') or die('Direct file access not allowed');



use Firebase\JWT\JWT;

function appcraft_login_user($request)
{
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $failed_attempts = get_transient('failed_login_attempts_' . $ip_address) ?: 0;

    if ($failed_attempts >= 5) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Too many login attempts, please try again later.', 'wp-app-craft')], 429);
    }
    $openid = sanitize_text_field($request['openid']);
    $login_type = sanitize_key($request['login_type']);
    $user = null;
    // error_log(' 登录类型：' . $login_type);
    // error_log(' 登录openid：' . $openid);
    switch ($login_type) {

        case 'email_code':
            $user = appcraft_handle_email_code_login($request);
            break;

        case 'phone_code':
            $user = appcraft_handle_phone_code_login($request);
            break;
        case 'openid':
            $user = appcraft_handle_openid_login($request);
            break;
        default:
            $user = appcraft_handle_password_login($request);
            break;
    }


    if ($user && !is_wp_error($user)) {
        return appcraft_finalize_login($user);
    } else {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Login failed', 'wp-app-craft')], 400);
    }
}
// 邮箱登录
function appcraft_handle_email_code_login($request)
{
    $email = $request['email'];
    $email_code = $request['email_code'];
    $stored_code = get_transient("email_code_" . $email);

    if (!$stored_code || $stored_code !== $email_code) {
        return new WP_Error('verification_failed', __('Incorrect or expired verification code', 'wp-app-craft'));
    }

    delete_transient("email_code_" . $email);
    return get_user_by('email', $email);
}
// 手机号登录
function appcraft_handle_phone_code_login($request)
{
    $phone_code = $request['phone_code'];
    $phoneInfo = appcraft_get_phone_number_from_wechat($phone_code);
    // error_log("User phoneInfo " . $phoneInfo['phoneNumber']);
    if (is_wp_error($phoneInfo)) {
        return $phoneInfo;
    }

    return appcraft_get_or_create_user_by_phone($phoneInfo['phoneNumber']);
}

// 密码登录
function appcraft_handle_password_login($request)
{
    $username_or_email = $request['username'];
    $password = $request['password'];
    $user = username_exists($username_or_email) ? get_user_by('login', $username_or_email) : get_user_by('email', $username_or_email);

    if (!$user || !wp_check_password($password, $user->data->user_pass, $user->ID)) {
        return new WP_Error('login_failed', __('Incorrect username, email, or password', 'wp-app-craft'));
    }

    return $user;
}
// 微信登录
function appcraft_handle_openid_login($request)
{
    $openid = $request['openid'];
    // error_log('openid：' . $openid);

    if (!$openid) {
        return new WP_Error('missing_openid', __('Missing OpenID', 'wp-app-craft'));
    }

    $user = appcraft_get_user_by_openid($openid);
    if (!$user) {
        // 处理用户不存在的情况
        return new WP_Error('user_not_found', __('User not found', 'wp-app-craft'));
    }

    return $user;
}


// 共用登录
function appcraft_finalize_login($user)
{
    delete_transient('failed_login_attempts_' . $_SERVER['REMOTE_ADDR']);
    $expiration_hours = carbon_get_theme_option('appcraft_jwt_expiration') ?: 24;
    $key = carbon_get_theme_option('appcraft_jwt_secret_key');
    $payload = [
        "user_id" => $user->ID,
        "exp" => time() + ($expiration_hours * 60 * 60)
    ];

    $token = JWT::encode($payload, $key, 'HS256');
    $nonce = wp_create_nonce('create_comment');

    $profile_request = new WP_REST_Request();
    $profile_request->set_header('Authorization', $token);
    $profile_response = appcraft_get_user_profile($profile_request);

    if (is_wp_error($profile_response)) {
        return $profile_response;
    }

    $profile_data = $profile_response->get_data();

    $response_data = [
        "code" => "200",
        "message" => "success",
        "token" => $token,
        'nonce' => $nonce,
        "data" => array_merge(
            [
                'id' => $user->ID,
                'userName' => $user->user_login,
                'nickname' => $user->nickname,
                'date' => $user->user_registered,
                'email' => $user->user_email,
                'roleId' => $user->roles[0],
                'roleName' => appcraft_translate_user_role($user->roles[0]),
            ],
            $profile_data['data']
        )
    ];

    return new WP_REST_Response($response_data, 200);
}

// 角色翻译函数
function appcraft_translate_user_role($role)
{
    global $wp_roles;
    return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role]) : $role;
}
