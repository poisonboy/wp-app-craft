<?php
defined('ABSPATH') or die('Direct file access not allowed');



function appcraft_search_posts(WP_REST_Request $request)
{
    // 获取搜索关键字和分页参数
    $keyword = $request->get_param('keyword');
    $page = $request->get_param('page') ?: 1;
    $per_page = $request->get_param('per_page') ?: 10;

    // 检查 keyword 参数是否存在
    if (empty($keyword)) {
        return new WP_REST_Response(array('status' => 'error', 'message' => 'No keywords'), 400);
    }

    // 设置查询参数
    $args = array(
        's' => $keyword,
        'paged' => $page,
        'posts_per_page' => $per_page,
    );

    // 查询文章
    $query = new WP_Query($args);

    // 处理查询结果
    $data = array();
    foreach ($query->posts as $post) {
        // 使用 appcraft_create_post_data 函数创建文章数据
        $data[] = appcraft_create_post_data($post);
    }

    // 构建响应
    $response = array(
        'code' => 200,
        'message' => 'success',
        'data' => $data,
        'total' => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'page' => $page,
        'size' => $per_page
    );

    return new WP_REST_Response($response, 200);
}
