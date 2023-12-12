<?php
defined('ABSPATH') or die('Direct file access not allowed');


function appcraft_get_user_by_openid($openid) {
    $args = array(
        'meta_key'   => 'openid',
        'meta_value' => $openid,
        'number' => 1
    );

    $users = get_users($args);

    if (!empty($users)) {
        return $users[0]; 
    }

    return null; 
}

 
 
function appcraft_get_openid_from_code(WP_REST_Request $request)
{
    $code = $request->get_param('code'); 
    $inviter_id = $request->get_param('inviter_id');
    // error_log('微信登录$inviter_id'.$inviter_id);
    $appid = carbon_get_theme_option('appcraft_wechat_appid');
    $secret = carbon_get_theme_option('appcraft_wechat_secret'); 
    $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$code&grant_type=authorization_code";
    $response = wp_remote_get($url);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    // Check if 'openid' is set and not empty in the response
    if (empty($data['openid'])) {
        // Handle error, for example, log it and return an error response
        // error_log('OpenID not found in response');
        return new WP_Error('openid_not_found', 'Failed to retrieve OpenID from WeChat API', array('status' => 400));
    }
    $openid = $data['openid'];

      // 获取或创建用户
    $user = appcraft_get_user_by_openid($openid); 

    // 检查用户是否存在
    if ($user === null) {
        // 用户不存在，进行一键注册
        $register_request = new WP_REST_Request();
        $register_request->set_param('openid', $openid);
        $register_request->set_param('inviter_id', $inviter_id);
        // error_log('正在进行一键注册');
        return appcraft_wx_register($register_request);
    } else {
        // 用户存在，进行登录
        $login_request = new WP_REST_Request();
         $login_request->set_param('openid', $openid);
        $login_request->set_param('login_type', 'openid');
        $login_request->set_param('user', $user); // 确保用户信息也被传递
        // error_log('用户存在，正在登录');
        return appcraft_login_user($login_request);
    }
}


function appcraft_get_wechat_access_token()
{
    // 尝试从缓存中获取access_token
    $cached_access_token = get_transient('wechat_access_token');
    if ($cached_access_token) {
        return $cached_access_token;
    }

    // 如果缓存中没有access_token，则请求新的
    $appid = carbon_get_theme_option('appcraft_wechat_appid');
    $secret = carbon_get_theme_option('appcraft_wechat_secret');
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $access_token = $data['access_token'];

    // 将新的access_token存储到缓存中，过期时间稍短于微信返回的过期时间
    if (!empty($access_token)) {
        // 微信默认过期时间为7200秒，这里我们设置为7000秒
        set_transient('wechat_access_token', $access_token, 7000);
    }

    return $access_token;
}
// 调用微信接口获取手机号的函数
function appcraft_get_phone_number_from_wechat($code)
{
    // 使用 appcraft_get_wechat_access_token 函数获取access_token
    $access_token = appcraft_get_wechat_access_token();
    if (is_wp_error($access_token)) {
        return $access_token;
    }

    $response = wp_remote_post("https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=$access_token", array(
        'body' => json_encode(array('code' => $code))
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['errcode']) && $data['errcode'] != 0) {
        return new WP_Error('wx_phone_error', $data['errmsg'], array('status' => 400));
    }

    return $data['phone_info'];
}
// 检查手机号是否已被注册
function appcraft_get_user_by_phone($phoneNumber, $exclude_user_id = null) {
    $args = array(
        'meta_key' => 'mobile',
        'meta_value' => $phoneNumber,
        'fields' => 'ID', // 只返回用户ID
    );

    $users = get_users($args);
    $userExists = !empty($users) && ($exclude_user_id === null || $users[0] != $exclude_user_id);

    return array($userExists ? $users[0] : null, $userExists);
}

function appcraft_get_or_create_user_by_phone($phoneNumber) {
    list($existing_user, $userExists) = appcraft_get_user_by_phone($phoneNumber);

    if ($existing_user) {
        return $existing_user;
    }

    if (!$userExists) {
        $request = new WP_REST_Request();
        $request->set_param('mobile', $phoneNumber);
        return appcraft_wx_register($request);
    }

    return new WP_Error('phone_number_exists', __('Phone number already registered', 'wp-app-craft'), array('status' => 400));
}
function appcraft_update_phone_number(WP_REST_Request $request)
{
    $user_id = appcraft_verify_user_token($request);

    // 获取用户当前的更新次数
    $current_update_times = carbon_get_user_meta($user_id, 'appcraft_phone_update_count');

    // 检查是否还有剩余的更新次数
    if ($current_update_times <= 0) {
        return new WP_Error('no_update_remaining', 'No phone number updates remaining.', array('status' => 403));
    }

    // 首先减少一次更新次数
    carbon_set_user_meta($user_id, 'appcraft_phone_update_count', $current_update_times - 1);

    $code = $request->get_param('code');

    // 获取手机号码
    $phone_info = appcraft_get_phone_number_from_wechat($code);
    if (is_wp_error($phone_info)) {
        // 如果获取手机号失败，可以选择是否恢复更新次数
        // carbon_set_user_meta($user_id, 'appcraft_phone_update_count', $current_update_times);
        return $phone_info;
    }

    $phone_number = $phone_info['phoneNumber'];
    list($_, $phoneExists) = appcraft_get_user_by_phone($phone_number, $user_id);
    if ($phoneExists) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Phone number already registered', 'wp-app-craft')], 400);
    }

    // 更新用户资料
    if (!empty($user_id) && !empty($phone_number)) {
        update_user_meta($user_id, 'mobile', $phone_number);
        return new WP_REST_Response(['message' => 'Phone number updated successfully'], 200);
    }

    return new WP_Error('update_failed', 'Failed to update phone number', array('status' => 400));
}

function appcraft_wx_register($request) {
    

    $randomID = appcraft_generate_random_id();
    $username = 'wx_' . $randomID;
    $nickname = __('Unknown User', 'wp-app-craft');  
    $password = wp_generate_password(12, true, true);
    $email = 'email_' . $randomID . '@weixin.com';

    // 使用从请求中获取的手机号码，如果没有提供，则设为空
    $mobile = $request->get_param('mobile') ? sanitize_text_field($request->get_param('mobile')) : '';
    $openid= $request->get_param('openid') ? sanitize_text_field($request->get_param('openid')) : '';
      
    $inviter_id = $request->get_param('inviter_id') ? sanitize_text_field($request->get_param('inviter_id')) : '';
    // error_log('注册时的$inviter_id'.$inviter_id);
    return appcraft_common_register_logic($username, $password, $email, $nickname, $inviter_id, $mobile, $openid);
}