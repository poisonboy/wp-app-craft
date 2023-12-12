<?php
defined('ABSPATH') or die('Direct file access not allowed');


function appcraft_time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $string = array(
        'y' => __('year', 'wp-app-craft'),
        'm' => __('month', 'wp-app-craft'),
        'd' => __('day', 'wp-app-craft'),
        'h' => __('hour', 'wp-app-craft'),
        'i' => __('minute', 'wp-app-craft'),
        's' => __('second', 'wp-app-craft'),
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ' . __('ago', 'wp-app-craft') : __('just now', 'wp-app-craft');
}


function appcraft_build_comment_tree($comments)
{
    $comments_by_id = array();
    foreach ($comments as $comment) {
        $author = get_user_by('id', $comment->user_id);
        $author_id = $author ? $author->ID : null;
        $author_nickname = $author ? $author->nickname : null;
        $comments_by_id[$comment->comment_ID] = array(
            'id' => $comment->comment_ID,
            'content' => $comment->comment_content,
            'date' => appcraft_time_elapsed_string($comment->comment_date),
            'authorId' => $author_id, // 添加的作者 ID
            'author' => $author_nickname, // 添加的作者昵称
            'author_avatar' => carbon_get_user_meta($comment->user_id, 'appcraft_avatar'),
            'postId' => $comment->comment_post_ID,
            'postTitle' => get_the_title($comment->comment_post_ID),
            'parentId' => $comment->comment_parent,
            'children' => array(),
        );
    }
    foreach ($comments_by_id as $comment_id => &$comment) { // 用引用遍历以便直接修改
        if ($comment['parentId'] != 0) {
            if (isset($comments_by_id[$comment['parentId']])) { // 检查父评论是否存在
                $parentComment = $comments_by_id[$comment['parentId']];
                $comment['replyTo'] = $parentComment['content'] ?? ''; // 使用null合并运算符
                $comment['replyToAuthor'] = $parentComment['author'] ?? '';
                $comment['replyToAvatar'] = $parentComment['author_avatar'] ?? '';
                $comment['replyToId'] = $parentComment['id'] ?? '';

                $parentId = $comment['parentId'];
                while (isset($comments_by_id[$parentId]) && $comments_by_id[$parentId]['parentId'] != 0) { // 检查父评论是否存在
                    $parentId = $comments_by_id[$parentId]['parentId'];
                }
                if (isset($comments_by_id[$parentId])) { // 检查评论是否存在
                    $comments_by_id[$parentId]['children'][] = $comment;
                }
            }
        }
    }

    $tree = array();
    foreach ($comments_by_id as $comment_id => $comment) {
        if ($comment['parentId'] == 0) {
            usort($comment['children'], function ($a, $b) {
                return strcmp($b['date'] ?? '', $a['date'] ?? ''); // 确保字符串不是null
            });
            $tree[] = $comment;
        }
    }

    usort($tree, function ($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? ''); // 确保字符串不是null
    });

    return $tree;
}

function appcraft_create_comment_response($comments)
{
    return array(
        'code' => '200',
        'message' => 'success',
        'data' => array(
            'list' => $comments,
            'total' => count($comments),
            'totalPages' => 1,  // Assuming all comments are returned in one page
            'page' => 1,
            'size' => count($comments),
        ),
    );
}

function appcraft_get_comments($data)
{
    if (isset($data['post_id']) && empty($data['post_id'])) {
        return appcraft_create_comment_response(array());
    }

    $args = array('status' => 'approve');
    if (!empty($data['post_id'])) {
        $args['post_id'] = $data['post_id'];
    }

    $comments = get_comments($args);
    $comment_tree = appcraft_build_comment_tree($comments);

    return appcraft_create_comment_response($comment_tree);
}
function appcraft_get_comments_by_user($data)
{
    if (isset($data['user_id']) && empty($data['user_id'])) {
        return appcraft_create_comment_response(array());
    }

    $args = array('status' => 'approve');
    if (!empty($data['user_id'])) {
        $args['user_id'] = $data['user_id'];
    }

    $comments = get_comments($args);
    $comment_tree = appcraft_build_comment_tree($comments);

    return appcraft_create_comment_response($comment_tree);
}
