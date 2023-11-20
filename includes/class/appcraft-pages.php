<?php 
 
 
 
function appcraft_get_page($request)
{
    $id = (int) $request['id'];
    $page = get_page($id);

    if (empty($page)) {
        return new WP_Error('appcraft_page_not_found', __('Page not found', 'app-craft'), array('status' => 404));
    }

    $data = array(
        'id' => $page->ID,
        'title' => $page->post_title,
        'content' => $page->post_content,
        'author' => get_the_author_meta('nickname', $page->post_author),
        'date' => $page->post_date,
        'image' => appcraft_get_post_image($page->ID),
    );

    return create_response($data);
}