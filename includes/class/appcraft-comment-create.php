<?php
function appcraft_create_comment($request) {
    // Verify and decode JWT
    $user_id = verify_user_token($request);
    wp_set_current_user($user_id);

    // Retrieve and validate input
    $post_id = $request['post_id'];
    $comment_content = sanitize_text_field($request['comment']);

    $parent_comment_id = $request['parent_comment_id']; // Parent comment ID, for replying

    if (empty($post_id) || empty($comment_content)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Post ID and comment content cannot be empty', 'app-craft')], 422);
    }

    // Check if the post ID is valid
    if (!get_post($post_id)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Invalid post ID', 'app-craft')], 422);
    }

    // Check for duplicate content
    if (is_duplicate_comment($user_id, $post_id, $comment_content)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Please do not submit duplicate comments', 'app-craft')], 409);
    }

    // Check comment frequency limit
    $time_limit = carbon_get_theme_option('appcraft_comment_time_limit') * MINUTE_IN_SECONDS;
    $max_comments_within_limit = carbon_get_theme_option('appcraft_max_comments');

    if (is_user_over_comment_limit($user_id, $time_limit, $max_comments_within_limit)) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Commenting too frequently, please try again later', 'app-craft')], 429);
    }

    // Retrieve the value of the comment moderation switch
    $comment_moderation_enabled = carbon_get_theme_option('appcraft_comment_moderation');

    // Create comment
    $comment_data = [
        'comment_post_ID' => $post_id,
        'comment_content' => $comment_content, 
        'user_id' => $user_id,
        'comment_approved' => $comment_moderation_enabled ? '0' : '1',
        'comment_parent' => $parent_comment_id,
    ];

    $comment_id = wp_insert_comment($comment_data);

    if (!$comment_id) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Failed to comment', 'app-craft')], 500);
    }

    // Clear post cache
    clean_post_cache($post_id);

    // Return success result
    $success_message = $comment_moderation_enabled
        ? __('Comment submitted successfully, will be visible after admin approval', 'app-craft')
        : __('Comment submitted successfully', 'app-craft');

    return new WP_REST_Response([
        'status' => 'success',
        'message' => $success_message,
        'data' => get_comment($comment_id),
    ], 200);
}

// Function to check for duplicate comments
function is_duplicate_comment($user_id, $post_id, $comment_content) {
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
function is_user_over_comment_limit($user_id, $time_limit, $max_comments) {
    $args = [
        'user_id' => $user_id,
        'date_query' => [
            'after' => date('Y-m-d H:i:s', current_time('timestamp') - $time_limit),
        ],
    ];

    $comments = get_comments($args);

    return count($comments) >= $max_comments;
}
