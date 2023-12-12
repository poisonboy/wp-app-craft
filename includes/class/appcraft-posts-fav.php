<?php
defined('ABSPATH') or die('Direct file access not allowed');


function appcraft_add_favorite($request)
{
    $user_id = appcraft_verify_user_token($request);
    $post_id = (int) $request['post_id'];

    if (!$user_id || !$post_id) {
        return new WP_Error('invalid_request', __('Invalid request', 'wp-app-craft'), array('status' => 400));
    }

    $favorites = get_user_meta($user_id, 'appcraft_favorites', true);
    if (!is_array($favorites)) {
        $favorites = [];
    }

    if (!in_array($post_id, $favorites)) {
        $favorites[] = $post_id;
        update_user_meta($user_id, 'appcraft_favorites', $favorites);
    }

    return new WP_REST_Response(['code' => 200, 'message' => __('Collection successful', 'wp-app-craft')]);
}
function appcraft_remove_favorite($request)
{
    $user_id = appcraft_verify_user_token($request);
    $post_id = (int) $request['post_id'];

    if (!$user_id || !$post_id) {
        return new WP_Error('invalid_request', __('Invalid request', 'wp-app-craft'), array('status' => 400));
    }


    $favorites = get_user_meta($user_id, 'appcraft_favorites', true);
    if (is_array($favorites) && in_array($post_id, $favorites)) {
        $favorites = array_diff($favorites, [$post_id]);
        update_user_meta($user_id, 'appcraft_favorites', $favorites);
    }

    return new WP_REST_Response(['code' => 200, 'message' => __('Unfavorite successful', 'wp-app-craft')]);
}

function appcraft_get_favorites($request)
{
    $user_id = appcraft_verify_user_token($request);
    $page = (int) $request['page'] ?: 1;
    $per_page = (int) $request['per_page'] ?: 10;

    $favorites = get_user_meta($user_id, 'appcraft_favorites', true);
    if (!is_array($favorites)) {
        $favorites = [];
    }
    $total = count($favorites);
    $total_pages = ceil($total / $per_page);
    $offset = ($page - 1) * $per_page;
    $paged_favorites = array_slice($favorites, $offset, $per_page);

    $posts = [];
    $posts = [];
    foreach ($paged_favorites as $post_id) {
        $post = get_post($post_id);
        if ($post) {
            $posts[] = appcraft_create_post_data($post);
        } else {

            continue;
        }
    }


    return new WP_REST_Response([
        'code' => 200,
        'data' => $posts,

        'total' => $total,
        'total_pages' => $total_pages,
        'page' => $page,
        'size' => $per_page,
    ]);
}
