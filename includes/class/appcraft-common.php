<?php
defined('ABSPATH') or die('Direct file access not allowed');


function appcraft_get_vip_levels()
{
    $vip_levels = carbon_get_theme_option('appcraft_vip_levels');
    $options = array('0' => 'Not VIP');

    if (is_array($vip_levels)) {
        foreach ($vip_levels as $index => $level) {
            $options[$index + 1] = $level['alias'];
        }
    }

    return $options;
}

// 发送邮箱验证码
function appcraft_send_email_code($request)
{
    $user_id = appcraft_verify_user_token($request);
    $email = sanitize_email($request['email']);
    $emailtype = $request['emailtype'];

    // 检查邮箱格式是否正确
    if (!is_email($email)) {
        return new WP_REST_Response(array('status' => 'error', 'message' => __('Incorrect email format', 'wp-app-craft')), 400);
    }

    // 如果提供了邮箱地址并且不是用于登录，检查唯一性
    if ($email && $emailtype !== 'login' && email_exists($email)) {
        return new WP_REST_Response(array('status' => 'error', 'message' => __('Email already registered', 'wp-app-craft')), 400);
    }

    // 生成并发送验证码
    $email_code = rand(100000, 999999);
    set_transient("email_code_" . $email, $email_code, 5 * 60);

    $is_email_sent = wp_mail($email, __('Email Verification Code', 'wp-app-craft'), __('Your verification code is:', 'wp-app-craft') . ' ' . $email_code . '，' . __('valid for 5 minutes', 'wp-app-craft'));

    // 处理发送结果
    if ($is_email_sent) {
        return new WP_REST_Response(array('status' => 'success', 'message' => __('Verification code sent', 'wp-app-craft')), 200);
    } else {
        // 直接处理发送失败的情况，不需要检查 mail() 函数
        return new WP_REST_Response(array('status' => 'error', 'message' => __('Failed to send verification code', 'wp-app-craft')), 400);
    }
}


// 生成复杂的随机ID
function appcraft_generate_random_id($length = 10)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $randomIndex = mt_rand(0, strlen($chars) - 1);
        $result .= $chars[$randomIndex];
    }
    return $result;
}
