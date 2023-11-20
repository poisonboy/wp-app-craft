<?php
function appcraft_get_user_points_log($request)
{
    global $wpdb;
    $user_id = verify_user_token($request);

    $table_name = $wpdb->prefix . 'appcraft_points_log';

    $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY time DESC", $user_id);

    $results = $wpdb->get_results($sql);
    if (empty($results)) {
        return new WP_Error('no_data', 'No data', array('status' => 404));
    }
    return new WP_REST_Response(['code' => 200, 'data' => $results]);
}
function appcraft_get_user_invites_log($request)
{
    global $wpdb;
    $user_id = verify_user_token($request);
    $table_name = $wpdb->prefix . 'appcraft_points_log';

    // 获取该用户邀请的用户的 ID
    $invites = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $table_name WHERE inviter_id = %d GROUP BY user_id", $user_id));

    // 获取邀请的用户的信息
    $invites_info = array();
    foreach ($invites as $invite_id) {
        $user_info = get_userdata($invite_id);
        if ($user_info) {
            $invites_info[] = array(
                'user_id' => $invite_id,
                "avatar" => get_user_meta($invite_id, '_appcraft_avatar', true),
                'nickname' => $user_info->nickname,
                'time' => $user_info->user_registered,
            );
        }
    }
    // 对数组按 registered 字段倒序排列
    usort($invites_info, function ($a, $b) {
        return strcmp($b['time'], $a['time']);
    });
    $results = $invites_info;
    return new WP_REST_Response(['code' => 200, 'data' => $results]);
}


function get_rewarded_articles_points($request)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appcraft_points_log';

    // 获取文章 ID
    $article_id =  (int) $request['id'];

    // 查询获取指定文章的积分记录

    // Translated event description
    $event_description = __('Reading ID as ', 'wp-app-craft') . $article_id . __(' articles earned reward', 'wp-app-craft');

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE event LIKE %s AND article_id = %d",
        $event_description,
        $article_id
    ));




    // 格式化输出
    $data = [];
    foreach ($results as $row) {
        $user_info = get_userdata($row->user_id);
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

    return new WP_REST_Response($data, 200);
}
