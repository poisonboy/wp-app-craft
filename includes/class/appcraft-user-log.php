<?php
defined('ABSPATH') or die('Direct file access not allowed');


function appcraft_get_user_points_log($request)
{
    global $wpdb;
    $user_id = appcraft_verify_user_token($request);

    $table_name = sanitize_key($wpdb->prefix . 'appcraft_points_log');

    $sql = $wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY time DESC", $user_id);
    $results = $wpdb->get_results($sql);

    if (empty($results)) {
        return new WP_Error('no_data', 'No data', array('status' => 404));
    }
    return new WP_REST_Response(['code' => 200, 'data' => $results]);
}
function appcraft_get_user_invites_log($request)
{
    global $wpdb;
    $user_id = appcraft_verify_user_token($request);
    $table_name = $wpdb->prefix . 'appcraft_points_log';

    // 获取所有类型为 'user_invited' 的记录
    $invites_records = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE type = %s",
        AC_TYPE_INVITATION
    ));

    $invites_info = array();
    foreach ($invites_records as $record) {
        // 从 event 字段中提取被邀请者的ID
        if (preg_match('/已邀请 ID 为 (\d+) 的新用户/', $record->event, $matches)) {
            $invitee_id = $matches[1];
            // 确认当前记录的 user_id 与给定的 user_id 匹配
            if ($record->user_id == $user_id) {
                $user_info = get_userdata($invitee_id);
                if ($user_info) {
                    $invites_info[] = array(
                        'user_id' => $invitee_id,
                        "avatar" => get_user_meta($invitee_id, '_appcraft_avatar', true),
                        'nickname' => $user_info->nickname,
                        'time' => $user_info->user_registered,
                    );
                }
            }
        }
    }

    // 对数组按 registered 字段倒序排列
    usort($invites_info, function ($a, $b) {
        return strcmp($b['time'], $a['time']);
    });

    return new WP_REST_Response(['code' => 200, 'data' => $invites_info]);
}



function appcraft_get_rewarded_articles($request)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appcraft_points_log';

    // 获取文章 ID
    $article_id = (int) $request['id'];

    // 查询获取指定文章的积分记录
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE type = %s AND article_id = %d",
        AC_TYPE_ARTICLE_READ,
        $article_id
    ));




    // 格式化输出
    $data = [];
    foreach ($results as $row) {
        $user_info = get_userdata($row->user_id);

        // 检查是否获取到有效的用户信息
        if ($user_info instanceof WP_User) {
            $username = $user_info->nickname;
            $avatar =  get_user_meta($row->user_id, '_appcraft_avatar', true);

            // 计算时间差
            $time_diff = human_time_diff(strtotime($row->time), current_time('timestamp')) . '前';

            $data[]  = [
                'user_id' => $row->user_id,
                'username' => $username,
                'avatar' => $avatar,
                'article_id' => $row->article_id,
                'points_earned' => $row->points_earned,
                'time' => $time_diff,
            ];
        }
    }

    return new WP_REST_Response($data, 200);
}
function appcraft_get_signin_log()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appcraft_points_log';

    // 查询 user_invited 和 check_in 类型的记录
    $sql = "SELECT id, user_id, event, points_earned, time, type
            FROM $table_name
            WHERE type IN ('check_in', 'user_invited')
            ORDER BY id DESC
            LIMIT 10";

    $results = $wpdb->get_results($sql);
    if (empty($results)) {
        return new WP_REST_Response(['code' => 404, 'message' => 'No data found']);
    }

    // 格式化数据
    $formatted_results = [];
    foreach ($results as $row) {
        $user_info = get_userdata($row->user_id);
        if ($user_info instanceof WP_User) {
            $username = $user_info->nickname;
            $avatar_url = get_user_meta($row->user_id, '_appcraft_avatar', true) ?: 'default_avatar_url';
        } else {
            $username = __('[Deleted User]', 'wp-app-craft');
            $avatar_url = 'default_avatar_url';
        }

        $row->username = $username;
        $row->avatar = $avatar_url;
        $row->time = human_time_diff(strtotime($row->time), current_time('timestamp')) . '前';
        $formatted_results[] = $row;
    }

    return new WP_REST_Response(['code' => 200, 'data' => $formatted_results]);
}
