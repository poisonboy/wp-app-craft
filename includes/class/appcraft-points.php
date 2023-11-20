<?php
// 在 WordPress 初始化时安排事件
add_action('init', 'schedule_daily_points_reset');

function schedule_daily_points_reset() {
    if (!wp_next_scheduled('reset_daily_points')) {
        wp_schedule_event(time(), 'daily', 'reset_daily_points');
    }
}

add_action('init', function() {
    if (isset($_GET['reset_daily_points'])) {
        do_daily_points_reset();
    }
});

// 每日积分重置的具体逻辑
add_action('reset_daily_points', 'do_daily_points_reset');

function do_daily_points_reset() {
    // 获取所有用户
    $users = get_users(array('fields' => 'ID'));  // 仅获取用户ID，减少数据量
  
    // 分批处理，每批处理100个用户
    $batches = array_chunk($users, 100);
  
    foreach ($batches as $batch) {
        foreach ($batch as $user_id) {
            // 检查缓存中是否有今天已经赚取的积分数据
            $today_earned_points = wp_cache_get($user_id, 'today_earned_points');
            if (false === $today_earned_points) {
                // 如果缓存中没有数据，从数据库中获取，并将数据缓存
                $today_earned_points = get_user_meta($user_id, 'today_earned_points', true);
                wp_cache_set($user_id, $today_earned_points, 'today_earned_points');
            }
            
            // 重置每日积分
            if ($today_earned_points != 0) {
                $reset_result = update_user_meta($user_id, 'today_earned_points', 0);
                if (false === $reset_result) {
                    // 如果更新失败，记录错误 
                    error_log("Failed to reset daily points for user $user_id");
                } else {
                    // 更新缓存
                    wp_cache_set($user_id, 0, 'today_earned_points');
                }
            }
        }
    }

    // 更新最后重置日期
    update_option('last_daily_reset', date('Y-m-d'));
}

function appcraft_manage_points($user_id, $points, $event, $operation = 'add', $article_id = null, $bypass_daily_limit = false) {
    if (!acquire_lock('appcraft_manage_points_lock')) {
        return new WP_Error('cannot_obtain_lock', __('Operation too frequent, please try again later.', 'wp-app-craft'), array('status' => 400));
    }
    
    // Check if points are negative
    if ($points < 0) {
        return new WP_Error('negative_points', __('Points cannot be negative, please provide a positive points value.', 'wp-app-craft'), array('status' => 400));
    }
    // 验证和获取当前用户积分
    $current_points = get_and_cache_user_points($user_id);

    // 执行积分操作
    $result = perform_points_operation($user_id, $current_points, $points, $operation, $bypass_daily_limit);
    if (is_wp_error($result)) {
        return $result;
    }
        // error_log("appcraft_manage_points called with article_id = $article_id");

    // 记录积分变动
    appcraft_log_points($user_id, $event, $points, $operation, $article_id);

    release_lock('appcraft_manage_points_lock');

    return true;  
}

// 获取并缓存用户积分
function get_and_cache_user_points($user_id) {
    $current_points = wp_cache_get($user_id, 'appcraft_user_points');
    if (false === $current_points) {
        $current_points = get_user_meta($user_id, 'appcraft_user_points', true) ?: 0;
        wp_cache_set($user_id, $current_points, 'appcraft_user_points');
    }
    return $current_points;
}

// 执行积分操作
function perform_points_operation($user_id, $current_points, $points_to_add, $operation, $bypass_daily_limit) {
    $daily_limit = carbon_get_theme_option('appcraft_daily_limit') ?: 0;
    $today_earned_points = get_and_cache_user_points($user_id, 'today_earned_points');

    if ($operation == 'subtract' && ($current_points - $points_to_add < 0)) {
        return new WP_Error('insufficient_points', sprintf(__('Your points are insufficient, your current account points are %s, and the points required for %s are %s.', 'wp-app-craft'), $current_points, $event, $points), array('status' => 400));
    }

    if (!$bypass_daily_limit && $operation == 'add' && ($today_earned_points + $points_to_add > $daily_limit)) {
    // 已达到每日积分上限，不再添加积分
    return true;
}
    // 执行添加或扣减积分
    $new_points = ($operation == 'subtract') ? $current_points - $points_to_add : $current_points + $points_to_add;
    update_user_meta($user_id, 'appcraft_user_points', $new_points);
    wp_cache_set($user_id, $new_points, 'appcraft_user_points');

    if ($operation == 'add') {
        $new_today_earned = $today_earned_points + $points_to_add;
        update_user_meta($user_id, 'today_earned_points', $new_today_earned);
        wp_cache_set($user_id, $new_today_earned, 'today_earned_points');
    }    
   

    return true;
}

// 获取锁
function acquire_lock($lock_key) {
    $lock_value = get_transient($lock_key);
    if ($lock_value) {
        return false;
    }
    set_transient($lock_key, true, 1);
    return true;
}

// 释放锁
function release_lock($lock_key) {
    delete_transient($lock_key);
}


// 积分记录
function appcraft_log_points($user_id, $event, $points, $operation, $article_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'appcraft_points_log';

    // 获取用户数据和元数据，并缓存它们以减少数据库查询
    $user = wp_cache_get($user_id, 'user_data');
    if (false === $user) {
        $user = get_userdata($user_id);
        wp_cache_set($user_id, $user, 'user_data');
    }

    $current_points = wp_cache_get($user_id, 'appcraft_user_points');
    if (false === $current_points) {
        $current_points = get_user_meta($user_id, 'appcraft_user_points', true);
        wp_cache_set($user_id, $current_points, 'appcraft_user_points');
    }

    $inviter_count = wp_cache_get($user_id, 'appcraft_inviter_count');
    if (false === $inviter_count) {
        $inviter_count = get_user_meta($user_id, 'appcraft_inviter_count', true);
        wp_cache_set($user_id, $inviter_count, 'appcraft_inviter_count');
    }

    $inviter_id = wp_cache_get($user_id, 'inviter_id');
    if (false === $inviter_id) {
        $inviter_id = get_user_meta($user_id, 'inviter_id', true);
        wp_cache_set($user_id, $inviter_id, 'inviter_id');
    }
    // error_log("appcraft_log_points called with article_id = $article_id");

    // 准备数据
$data = [
    'user_id' => $user_id,
    'username' => $user->user_login,
    'event' => $event,
    'points_earned' => ($operation == 'subtract' ? -$points : $points),  // 更新这里
    'inviter_id' => $inviter_id,
    'current_points' => $current_points,
    'inviter_count' => $inviter_count,
    'article_id' => $article_id,
    'time' => current_time('mysql'),
];

// 插入数据
$insert_result = $wpdb->insert($table_name, $data);
    
    if (false === $insert_result) {
        // 插入失败，记录错误（可选）
        error_log("Failed to log points for user $user_id: {$wpdb->last_error}");
        return new WP_Error('db_insert_error', 'Datebase insert error', array('status' => 500));
    }

    return true;
}

