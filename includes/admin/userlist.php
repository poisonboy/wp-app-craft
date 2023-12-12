<?php
defined('ABSPATH') or die('Direct file access not allowed');
// 用户列表显示积分、id和昵称
add_filter('manage_users_columns', 'appcraft_add_user_columns');
function appcraft_add_user_columns($columns)
{
    $columns['nickname'] = __('Nickname', 'wp-app-craft');
    $columns['user_id'] = __('User ID', 'wp-app-craft');
    $columns['total_points'] = __('Total Points', 'wp-app-craft');
    $columns['mobile'] = __('Mobile', 'wp-app-craft');
    $columns['inviter_id'] = __('Inviter ID', 'wp-app-craft');
    return $columns;
}

// 填充新列的内容
add_action('manage_users_custom_column', 'appcraft_fill_user_columns', 2, 3);
function appcraft_fill_user_columns($value, $column_name, $user_id)
{
    $user = get_userdata($user_id);
    switch ($column_name) {
        case 'nickname':
            return $user->nickname;
        case 'user_id':
            return $user_id;
        case 'total_points':
            return appcraft_get_and_cache_user_points($user_id);
        case 'mobile':
            return get_user_meta($user_id, 'mobile', true);
        case 'inviter_id':
            return get_user_meta($user_id, 'inviter_id', true);
        default:
    }
    return $value;
}
//  添加可排序的列
add_filter('manage_users_sortable_columns', 'appcraft_make_user_id_column_sortable');
function appcraft_make_user_id_column_sortable($columns)
{
    $columns['user_id'] = 'ID';
    return $columns;
}

//   实现排序逻辑
add_action('pre_user_query', 'appcraft_users_orderby_id');
function appcraft_users_orderby_id($userquery)
{
    if ('ID' == $userquery->get('orderby')) {
        $userquery->query_orderby = "ORDER BY ID " . $userquery->get('order');
    }
}
