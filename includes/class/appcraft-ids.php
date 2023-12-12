<?php
defined('ABSPATH') or die('Direct file access not allowed');



// Add new columns to the post list
add_filter('manage_posts_columns', 'appcraft_add_post_columns');
function appcraft_add_post_columns($columns)
{
    $columns['post_id'] = __('Post ID', 'wp-app-craft');
    $columns['categories'] = __('Categories', 'wp-app-craft');
    $columns['tags'] = __('Tags', 'wp-app-craft');
    return $columns;
}

// Fill the new columns with data
add_action('manage_posts_custom_column', 'appcraft_fill_post_columns', 10, 2);
function appcraft_fill_post_columns($column_name, $post_id)
{
    if ($column_name == 'post_id') {
        echo $post_id;
    } elseif ($column_name == 'categories') {
        $categories = get_the_category($post_id);
        $category_names = array_map(function ($category) {
            return $category->name;
        }, $categories);
        echo implode(', ', $category_names);
    } elseif ($column_name == 'tags') {
        $tags = get_the_tags($post_id);
        if ($tags) {
            $tag_names = array_map(function ($tag) {
                return $tag->name;
            }, $tags);
            echo implode(', ', $tag_names);
        }
    }
}

// Add new columns to the page list
add_filter('manage_pages_columns', 'appcraft_add_page_columns');
function appcraft_add_page_columns($columns)
{
    $columns['page_id'] = __('Page ID', 'wp-app-craft');
    return $columns;
}

// Fill the new columns with data
add_action('manage_pages_custom_column', 'appcraft_fill_page_columns', 10, 2);
function appcraft_fill_page_columns($column_name, $post_id)
{
    if ($column_name == 'page_id') {
        echo $post_id;
    }
}

// Add new columns to the category list
add_filter('manage_edit-category_columns', 'appcraft_add_category_columns');
function appcraft_add_category_columns($columns)
{
    $columns['category_id'] = __('Category ID', 'wp-app-craft');
    return $columns;
}

// Fill the new columns with data
add_filter('manage_category_custom_column', 'appcraft_fill_category_columns', 10, 3);
function appcraft_fill_category_columns($out, $column_name, $term_id)
{
    if ($column_name == 'category_id') {
        $out = $term_id;
    }
    return $out;
}

// Add new columns to the tag list
add_filter('manage_edit-post_tag_columns', 'appcraft_add_tag_columns');
function appcraft_add_tag_columns($columns)
{
    $columns['tag_id'] = __('Tag ID', 'wp-app-craft');
    return $columns;
}

// Fill the new columns with data
add_filter('manage_post_tag_custom_column', 'appcraft_fill_tag_columns', 10, 3);
function appcraft_fill_tag_columns($out, $column_name, $term_id)
{
    if ($column_name == 'tag_id') {
        $out = $term_id;
    }
    return $out;
}
