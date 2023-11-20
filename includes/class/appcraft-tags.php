<?php 
 

function appcraft_get_tags($request)
{
    $tags = get_tags();
    $data = array();
    foreach ($tags as $tag) {
        $tag_data = array(
            'id' => $tag->term_id,
            'name' => $tag->name,
            'description' => $tag->description,
            'count' => $tag->count,
            'image' => wp_get_attachment_url(get_term_meta($tag->term_id, '_appcraft_thumbnail_image', true)),
            'cover' => wp_get_attachment_url(get_term_meta($tag->term_id, '_appcraft_cover_image', true)),
        );
        $data[] = $tag_data;
    }
    return create_response($data);
}

function appcraft_get_tag($request)
{
    $id = (int) $request['id'];
    $tag = get_tag($id);

    if (empty($tag)) {
        return new WP_Error('appcraft_tag_not_found', __('Tag not found', 'app-craft'), array('status' => 404));
    }

    $data = array(
        'id' => $tag->term_id,
        'name' => $tag->name,
        'description' => $tag->description,
        'count' => $tag->count,
        'image' => wp_get_attachment_url(get_term_meta($tag->term_id, '_appcraft_thumbnail_image', true)),
        'cover' => wp_get_attachment_url(get_term_meta($tag->term_id, '_appcraft_cover_image', true)),
    );

    return create_response($data);
}