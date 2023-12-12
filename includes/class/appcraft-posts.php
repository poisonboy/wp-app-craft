<?php
defined('ABSPATH') or die('Direct file access not allowed');


function appcraft_extract_images_from_content($content)
{
    preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
    return $matches[1];
}

function appcraft_get_post_image($post_id)
{
    if (has_post_thumbnail($post_id)) {
        $img_id  = get_post_thumbnail_id($post_id);
        $img_url = wp_get_attachment_image_src($img_id);
        $img_url = $img_url[0];
    } else {
        $content_post = get_post($post_id);
        $content = $content_post->post_content;
        $images = appcraft_extract_images_from_content($content);
        $img_url = !empty($images) ? $images[0] : null;
    }
    return $img_url;
}

function appcraft_get_post_images($post_id)
{
    $content_post = get_post($post_id);
    $content = $content_post->post_content;
    $images = appcraft_extract_images_from_content($content);
    return array_slice($images, 0, 9);
}
function appcraft_format_content($content)
{
    // 替换视频短代码
    $pattern_video = '/\[video\s+mp4="(.*?)"\]\[\/video\]/i';
    $replacement_video = '<video src="$1"></video>';
    $content = preg_replace($pattern_video, $replacement_video, $content);

    // 替换画廊短代码
    $pattern_gallery = '/\[gallery.*ids="([\d,]+)"(?:.*columns="(\d+)")?.*\]/';
    preg_match_all($pattern_gallery, $content, $gallery_matches, PREG_SET_ORDER);
    foreach ($gallery_matches as $match) {
        $ids = explode(',', $match[1]);
        $columns = isset($match[2]) ? intval($match[2]) : 3;
        $gallery_html = '<div class="gallery" style="display: flex; flex-wrap: wrap;">';
        $item_width = 100 / $columns . '%'; // 每个项目占据的百分比宽度
        foreach ($ids as $id) {
            $image_src = wp_get_attachment_url($id);
            $gallery_html .= '<div class="gallery-item" style="width: ' . $item_width . ';"><img src="' . $image_src . '" style="width: 100%; height: auto;"/></div>';
        }

        $gallery_html .= '</div>';
        $content = str_replace($match[0], $gallery_html, $content);
    }
    return $content;
}



function appcraft_create_post_data($post, $user_id = null)
{

    $category_ids = wp_get_post_categories($post->ID);
    $categories = array();
    foreach ($category_ids as $id) {
        $category = get_category($id);
        $categories[] = array(
            'id' => $id,
            'name' => $category->name,
            'image' => wp_get_attachment_url(get_term_meta($id, '_appcraft_thumbnail_image', true))
        );
    }
    $custom_taxonomy_terms = wp_get_post_terms($post->ID);
    $custom_terms = array();
    foreach ($custom_taxonomy_terms as $term) {
        $custom_terms[] = array(
            'id' => $term->term_id,
            'name' => $term->name,
            'image' => wp_get_attachment_url(get_term_meta($term->term_id, '_appcraft_thumbnail_image', true))
        );
    }
    $tag_ids = wp_get_post_tags($post->ID);
    $tags = array();
    foreach ($tag_ids as $id) {
        $tag = get_tag($id);
        $tags[] = array(
            'id' => $id,
            'name' => $tag->name,
            'image' => wp_get_attachment_url(get_term_meta($id, '_appcraft_thumbnail_image', true))
        );
    }
    $author_avatar_url = carbon_get_user_meta($post->post_author, 'appcraft_avatar');


    $is_favorited = false;
    if ($user_id) {
        $favorites = get_user_meta($user_id, 'appcraft_favorites', true);
        $is_favorited = is_array($favorites) && in_array($post->ID, $favorites);
    }


    // 检查文章是否有付费内容和奖励积分设置
    $paid_content = get_post_meta($post->ID, '_appcraft_paid_content', true);
    $read_type = get_post_meta($post->ID, '_appcraft_read_type', true);
    $pay_points = get_post_meta($post->ID, '_appcraft_pay_points', true);
    $reward_points = get_post_meta($post->ID, '_appcraft_reward_points', true);


    // 默认状态
    $has_payed = false;
    $has_earned = false;
    // 如果用户已登录，检查是否已支付或已获得奖励
    if ($user_id) {
        // error_log('user_id: ' . $user_id); 
        $has_payed = get_user_meta($user_id, '_appcraft_paid_points_' . $post->ID, true)  ==  'payed';

        $has_earned = get_user_meta($user_id, '_appcraft_read_earned_' . $post->ID, true) ==  'earned';

        if ($has_payed) {
            $paid_content = get_post_meta($post->ID, '_appcraft_paid_content', true);
        }
    }

    return array(
        'id' => $post->ID,
        'title' => $post->post_title ? str_replace("\n", " ", trim($post->post_title)) : '',
        'content' => $post->post_content ? appcraft_format_content($post->post_content) : '',
        'desc' => $post->post_content ? substr(trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", strip_tags($post->post_content)))), 0, 140) : '',
        'author' => array(
            'name' => get_the_author_meta('nickname', $post->post_author),
            'avatar' => $author_avatar_url,
        ),
        'date_only' => date('Y-m-d', strtotime($post->post_date)),
        'date' => $post->post_date,
        'image' => appcraft_get_post_image($post->ID),
        'imglist' => appcraft_get_post_images($post->ID),
        'categories' => $categories,
        'custom_taxonomy' => $custom_terms,
        'tags' => $tags,
        // 'appcraft_enable_reward_video' => get_post_meta($post->ID, '_appcraft_enable_reward_video', true),
        'comment_count' => (int)$post->comment_count,
        'format' => get_post_format($post->ID) ?: 'standard',
        'is_sticky' => is_sticky($post->ID),
        'is_favorited' => $is_favorited,
        'read_type' => $read_type,
        'read_fields' => array(
            'has_payed' => $has_payed,
            'has_earned' => $has_earned,
            'reward_points' => $reward_points,
            'pay_points' => $pay_points,

        ),
        'paid_content'  => $paid_content
        // 'points' => $points
    );
}

function appcraft_adjust_query_args($args, $request)
{
    if (isset($request['sticky']) && $request['sticky'] == 'true') {
        $args['post__in'] = get_option('sticky_posts');
    }
    if (isset($request['rand']) && $request['rand'] == 'true') {
        $args['orderby'] = 'rand';
    }
    if (isset($request['category'])) {
        $args['cat'] = $request['category'];  // 用于标准WordPress分类


    }
    if (isset($request['tag_id'])) {
        $args['tag_id'] = $request['tag_id'];
    }
    if (isset($request['author_id'])) {
        $args['author'] = $request['author_id']; // 添加作者ID到查询参数
    }
    return $args;
}

function appcraft_get_posts($request)
{
    $args = array(
        'posts_per_page' => $request['per_page'],
        'paged' => $request['page'],
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => 1
    );

    $args = appcraft_adjust_query_args($args, $request);
    $user_id = appcraft_verify_user_token($request);
    $query = new WP_Query($args);

    $data = array();
    foreach ($query->posts as $post) {
        $data[] = appcraft_create_post_data($post, $user_id);
    }

    $response = array(
        'code' => 200,
        'message' => '请求成功',
        'data' => $data,
        'total' => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'page' => $request['page'],
        'size' => $request['per_page']
    );

    return new WP_REST_Response($response);
}

function appcraft_get_post($request)
{

    $id = (int) $request['id'];
    $user_id = appcraft_verify_user_token($request);

    $post = get_post($id);

    if (empty($post) || $post->post_status !== 'publish') {
        return new WP_Error('post_not_found', __('Post not found', 'wp-app-craft'), array('status' => 404));
    }

    $tags = get_the_tags($post->ID);
    $tag_ids = $tags ? array_map(function ($tag) {
        return $tag->term_id;
    }, $tags) : array();
    $related_posts = array();
    if (!empty($tag_ids)) {
        $related_posts_args = array(
            'tag__in' => $tag_ids,
            'post__not_in' => array($post->ID),
            'posts_per_page' => 5
        );
        $related_posts_query = new WP_Query($related_posts_args);
        $related_posts = $related_posts_query->have_posts() ? array_map('appcraft_create_post_data', $related_posts_query->posts) : array();
    }



    $post_data = appcraft_create_post_data($post, $user_id);

    $post_data['related_posts'] = $related_posts;

    $response = array(
        'code' => 200,
        'message' => 'success',
        'data' => $post_data,
    );

    return new WP_REST_Response($response);
}
