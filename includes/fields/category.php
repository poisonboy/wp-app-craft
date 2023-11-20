<?php
 
use Carbon_Fields\Container;
use Carbon_Fields\Field;

function appcraft_add_category_fields() {
    // Add fields to categories and tags
    foreach (array('category', 'post_tag') as $taxonomy) {
        Container::make('term_meta', __( 'Category Image', 'app-craft' ))
            ->where('term_taxonomy', '=', $taxonomy)
            ->add_fields(array(
                Field::make('image', 'appcraft_thumbnail_image', __( 'Thumbnail Image', 'app-craft' ))->set_visible_in_rest_api( $visible = true ),
                // Field::make('image', 'appcraft_cover_image', __( 'Cover Image', 'app-craft' ))->set_visible_in_rest_api( $visible = true ),
            ));
    }

    // Add field to posts
    // Container::make('post_meta', __( 'Video Settings', 'app-craft' ))
    //     ->where('post_type', '=', 'post') // Only for posts
    //     ->add_fields(array(
    //         Field::make('checkbox', 'appcraft_enable_reward_video', __( 'Enable Reward Video', 'app-craft' ))
    //             ->set_option_value('yes')
    //             ->set_visible_in_rest_api( $visible = true )
    //     ));
}

add_action('carbon_fields_register_fields', 'appcraft_add_category_fields');

 