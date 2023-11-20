<?php
function appcraft_signin_api($request) {
   $user_id = verify_user_token($request);


    // 运行签到逻辑
    $signin_result = appcraft_user_sign_in($user_id);

    // 检查签到是否成功
    if (is_wp_error($signin_result)) {
        return $signin_result;
    }

    return new WP_REST_Response(['message' => __('Check-in successful', 'wp-app-craft')], 200);

}
function appcraft_user_sign_in($user_id) {
    $today = date('Y-m-d');
    $last_signin_date = get_user_meta($user_id, 'last_signin_date', true);
    $consecutive_days = get_user_meta($user_id, 'consecutive_days', true) ?: 0;

    // 检查用户今天是否已经签到
    if ($last_signin_date === $today) {
        return new WP_Error('already_signed_in', __('Already checked in today', 'wp-app-craft'), array('status' => 400));
    }

    // 获取积分设置
    $signin_points = carbon_get_theme_option('appcraft_signin_points') ?: 0;
    $seven_day_bonus = carbon_get_theme_option('appcraft_signin_7day_points') ?: 0;

    // 管理积分
    $result = appcraft_manage_points($user_id, $signin_points, __('User check-in', 'wp-app-craft'));

    if (is_wp_error($result)) {
        return $result;
    }

    // 更新签到日期和连续天数
    update_user_meta($user_id, 'last_signin_date', $today);
    $consecutive_days += 1;

    // 检查是否达到连续7天
    if ($consecutive_days == 7) {
        appcraft_manage_points($user_id, $seven_day_bonus, __('7-day consecutive check-in reward', 'wp-app-craft'));
    } elseif ($consecutive_days > 7) {
        $consecutive_days = 1; // 第8天重置连续天数
    }

    update_user_meta($user_id, 'consecutive_days', $consecutive_days);

    return array('message' => __('Check-in successful', 'wp-app-craft'));
}
