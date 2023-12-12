<?php
defined('ABSPATH') or die('Direct file access not allowed');


function appcraft_create_comment($request) {
 
    $user_id = appcraft_verify_user_token($request);
    wp_set_current_user($user_id);

 
    $post_id = $request['post_id'];
    $comment_content = sanitize_text_field($request['comment']);

    $parent_comment_id = $request['parent_comment_id']; // Parent comment ID, for replying

    if (empty($post_id) || empty($comment_content)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Post ID and comment content cannot be empty', 'wp-app-craft')], 422);
    }

 
    if (!get_post($post_id)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Invalid post ID', 'wp-app-craft')], 422);
    }

 
    if (appcraft_is_duplicate_comment($user_id, $post_id, $comment_content)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Please do not submit duplicate comments', 'wp-app-craft')], 409);
    }

  
    $time_limit = carbon_get_theme_option('appcraft_comment_time_limit') * MINUTE_IN_SECONDS;
    $max_comments_within_limit = carbon_get_theme_option('appcraft_max_comments');

    if (appcraft_is_user_over_comment_limit($user_id, $time_limit, $max_comments_within_limit)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Commenting too frequently, please try again later', 'wp-app-craft')], 429);
    }

 
    $comment_moderation_enabled = carbon_get_theme_option('appcraft_comment_moderation');

  
    $comment_data = [
        'comment_post_ID' => $post_id,
        'comment_content' => $comment_content, 
        'user_id' => $user_id,
        'comment_approved' => $comment_moderation_enabled ? '0' : '1',
        'comment_parent' => $parent_comment_id,
    ];

    $comment_id = wp_insert_comment($comment_data);

    if (!$comment_id) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Failed to comment', 'wp-app-craft')], 500);
    }

 
    clean_post_cache($post_id);
 
    $success_message = $comment_moderation_enabled
        ? __('Comment submitted successfully, will be visible after admin approval', 'wp-app-craft')
        : __('Comment submitted successfully', 'wp-app-craft');

    return new WP_REST_Response([
        'status' => 'success',
        'message' => $success_message,
        'data' => get_comment($comment_id),
    ], 200);
}

 
function appcraft_is_duplicate_comment($user_id, $post_id, $comment_content) {
    global $wpdb;
    $time_limit = carbon_get_theme_option('appcraft_comment_time_limit') * MINUTE_IN_SECONDS;

    $time_now = current_time('mysql', true); // Get GMT time

    $query = $wpdb->prepare("
        SELECT COUNT(*) FROM $wpdb->comments
        WHERE comment_post_ID = %d
        AND comment_content = %s
        AND user_id = %d
        AND comment_date_gmt > DATE_SUB(%s, INTERVAL %d SECOND)
    ", $post_id, $comment_content, $user_id, $time_now, $time_limit);

    $duplicate = $wpdb->get_var($query);

    return $duplicate > 0;
}

// Function to check if a user has exceeded the comment limit
function appcraft_is_user_over_comment_limit($user_id, $time_limit, $max_comments) {
    $args = [
        'user_id' => $user_id,
        'date_query' => [
            'after' => date('Y-m-d H:i:s', current_time('timestamp') - $time_limit),
        ],
    ];

    $comments = get_comments($args);

    return count($comments) >= $max_comments;
}
