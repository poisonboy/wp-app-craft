<?php

 use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function verify_token($token) {
    if ($token === null) {
        // 如果令牌为 null，则返回错误或 false
        return false;
    }

    $key = carbon_get_theme_option('appcraft_jwt_secret_key'); // 同样的密钥
    $token = $token ? str_replace('Bearer ', '', $token) : '';

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        return $decoded ? $decoded : false;
    } catch (Exception $e) {
        // 记录异常信息
        return false;
    }
}

 
function verify_user_token(WP_REST_Request $request) {
    // 从请求头获取令牌
    $token = $request->get_header('Authorization');

    // 验证令牌
    $decoded = verify_token($token);

    // 检查验证结果
    if (is_wp_error($decoded) || !isset($decoded->user_id)) {
        // 直接在这里处理错误情况
        return new WP_Error('jwt_not_logged_in', __('User not logged in or token is invalid', 'app-craft'), array('status' => 401));
    }

    // 返回用户ID
    return $decoded->user_id;
}

