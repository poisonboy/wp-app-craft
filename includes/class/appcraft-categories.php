<?php
defined('ABSPATH') or die('Direct file access not allowed');

  
function appcraft_get_categories($request) {
    $args = isset($request['category']) ? array('include' => explode(',', $request['category']), 'hide_empty' => 0) : array('hide_empty' => 0);
    $categories = get_categories($args);

    $parent_ids = array(); // 创建一个数组来存储所有的父分类 ID
    foreach($categories as $category) {
        $parent_ids[] = $category->parent; // 添加每个分类的父 ID 到数组中
    }
    $parent_ids = array_unique($parent_ids); // 移除数组中的重复项

    $data = array();
    foreach($parent_ids as $parent_id) {
        $data = array_merge($data, appcraft_build_category_tree($categories, $parent_id)); // 为每个父分类 ID 构建分类树
    }

    return new WP_REST_Response($data, 200);
}

function appcraft_build_category_tree($categories, $parent = 0) {
    $data = array();
    foreach ($categories as $category) {
        if ($category->parent == $parent) {
            $children = appcraft_build_category_tree($categories, $category->term_id);
            $category_data = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'description' => $category->description,
                'count' => $category->count,
                'image' => wp_get_attachment_url(get_term_meta($category->term_id, '_appcraft_thumbnail_image', true)),
                'cover' => wp_get_attachment_url(get_term_meta($category->term_id, '_appcraft_cover_image', true)),
                'children' => $children
            );
            $data[] = $category_data;
        }
    }
    return $data;
}


  
