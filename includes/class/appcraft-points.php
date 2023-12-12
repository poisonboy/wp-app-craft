<?php
defined('ABSPATH') or die('Direct file access not allowed');


// 在 WordPress 初始化时安排事件
add_action('init', 'appcraft_schedule_daily_points_reset');

function appcraft_schedule_daily_points_reset()
{
    if (!wp_next_scheduled('reset_daily_points')) {
        wp_schedule_event(time(), 'daily', 'reset_daily_points');
    }
}

add_action('init', function () {
    if (isset($_GET['reset_daily_points'])) {
        appcraft_do_daily_points_reset();
    }
});

// 每日积分重置的具体逻辑
add_action('reset_daily_points', 'appcraft_do_daily_points_reset');

function appcraft_do_daily_points_reset()
{
    // 获取所有用户
    $users = get_users(array('fields' => 'ID'));  // 仅获取用户ID，减少数据量

    // 分批处理，每批处理100个用户
    $batches = array_chunk($users, 10);

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
function appcraft_get_and_cache_user_meta($user_id, $meta_key)
{
    $meta_value = wp_cache_get($user_id, $meta_key);
    if (false === $meta_value) {
        $meta_value = get_user_meta($user_id, $meta_key, true);
        wp_cache_set($user_id, $meta_value, $meta_key);
    }
    return $meta_value;
}
function appcraft_manage_points($user_id, $points, $event, $operation, $type, $article_id = null, $bypass_daily_limit = false)
{
    if (!appcraft_acquire_lock('appcraft_manage_points_lock')) {
        return new WP_Error('cannot_obtain_lock', __('Operation too frequent, please try again later.', 'wp-app-craft'), array('status' => 400));
    }

    // Check if points are negative
    if ($points < 0) {
        return new WP_Error('negative_points', __('Points cannot be negative, please provide a positive points value.', 'wp-app-craft'), array('status' => 400));
    }
    // 验证和获取当前用户积分
    $current_points = appcraft_get_and_cache_user_points($user_id);

    // 执行积分操作
    $result = appcraft_perform_points_operation($user_id, $points, $event, $operation, $type, $bypass_daily_limit);
    if (is_wp_error($result)) {
        return $result;
    }
    // error_log("appcraft_manage_points called with article_id = $article_id");

    // 记录积分变动
    appcraft_log_points($user_id, $points, $event, $operation, $type, $article_id);

    appcraft_release_lock('appcraft_manage_points_lock');

    return true;
}

// 获取并缓存用户积分
function appcraft_get_and_cache_user_points($user_id)
{
    $current_points = wp_cache_get($user_id, 'appcraft_user_points');
    if (false === $current_points) {
        $current_points = get_user_meta($user_id, 'appcraft_user_points', true) ?: 0;
        wp_cache_set($user_id, $current_points, 'appcraft_user_points');
    }
    return $current_points;
}

// 执行积分操作
function appcraft_perform_points_operation($user_id, $points, $event, $operation, $type, $bypass_daily_limit)
{
    // 获取每日积分上限
    $daily_limit = carbon_get_theme_option('appcraft_daily_limit') ?: 0;

    // 获取当前用户积分和今天已获得的积分，使用缓存优化
    $current_points = appcraft_get_and_cache_user_points($user_id);
    $today_earned_points = appcraft_get_and_cache_user_points($user_id, 'today_earned_points');

    // 判断积分是否足够扣除
    if ($operation === 'subtract' && ($current_points - $points < 0)) {
        return new WP_Error(
            'insufficient_points',
            sprintf(__('Your points are insufficient, your current account points are %s, and the points required for %s are %s.', 'wp-app-craft'), $current_points, $event, $points),
            array('status' => 400)
        );
    }

    // 检查是否超过每日积分上限，如果没有超过则添加积分
    if (!$bypass_daily_limit && $operation === 'add' && ($today_earned_points + $points > $daily_limit)) {
        // 已达到每日积分上限，不再添加积分
        return true;
    }

    // 计算新的积分值
    $new_points = $operation === 'subtract' ? $current_points - $points : $current_points + $points;

    // 更新用户的积分
    update_user_meta($user_id, 'appcraft_user_points', $new_points);

    // 更新积分缓存
    wp_cache_set($user_id, $new_points, 'appcraft_user_points');

    // 如果是添加操作，同时更新今日已获得的积分
    if ($operation === 'add') {
        $new_today_earned_points = $today_earned_points + $points;
        update_user_meta($user_id, 'today_earned_points', $new_today_earned_points);
        wp_cache_set($user_id, $new_today_earned_points, 'today_earned_points');
    }

    return true;
}

// 获取锁
function appcraft_acquire_lock($lock_key)
{
    $lock_value = get_transient($lock_key);
    if ($lock_value) {
        return false;
    }
    set_transient($lock_key, true, 1);
    return true;
}

// 释放锁
function appcraft_release_lock($lock_key)
{
    delete_transient($lock_key);
}


// 积分记录
function appcraft_log_points($user_id, $points, $event, $operation, $type, $article_id = null)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appcraft_points_log';

    // 使用缓存优化获取用户当前积分
    $current_points = appcraft_get_and_cache_user_points($user_id);

    // 获取邀请人数和邀请者ID，使用缓存优化
    $inviter_count = appcraft_get_and_cache_user_meta($user_id, 'appcraft_inviter_count');
    $inviter_id = appcraft_get_and_cache_user_meta($user_id, 'inviter_id');

    // 构建要插入的数据
    $data = [
        'user_id' => $user_id,
        'event' => $event,
        'points_earned' => ($operation === 'subtract' ? -$points : $points),
        'inviter_id' => $inviter_id,
        'current_points' => $current_points,
        'inviter_count' => $inviter_count,
        'article_id' => $article_id,
        'time' => current_time('mysql'),
        'type' => $type,
    ];

    // 插入数据到数据库
    $insert_result = $wpdb->insert($table_name, $data);

    if (false === $insert_result) {
        // 插入失败时记录错误
        error_log("Failed to log points for user $user_id: {$wpdb->last_error}");
        return new WP_Error('db_insert_error', 'Database insert error', array('status' => 500));
    }

    return true;
}



function appcraft_delete_user_points_log($user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appcraft_points_log';

    // 删除对应用户ID的积分记录
    $wpdb->delete($table_name, ['user_id' => $user_id]);

    // 清除相关的缓存
    wp_cache_delete($user_id, 'today_earned_points');
    wp_cache_delete($user_id, 'appcraft_user_points');
    wp_cache_delete($user_id, 'user_data');
    wp_cache_delete($user_id, 'appcraft_inviter_count');
    wp_cache_delete($user_id, 'inviter_id');
}
add_action('deleted_user', 'appcraft_delete_user_points_log');
