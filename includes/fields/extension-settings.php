<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;
 
function appcraft_register_extension_fields()
{
    $employees_labels = array(
        'plural_name' => __('Items', 'app-craft'),
        'singular_name' => __('Item', 'app-craft'),
    );
     
    // 创建扩展设置子菜单
    $extension_settings = Container::make('theme_options', __('Extension Settings', 'app-craft'))
        ->set_page_parent('appcraftbuilder')  
         ->set_page_file('appcraft_extension_settings')  
        ->add_tab(__('Carousel', 'app-craft'), array(
            Field::make('complex', 'appcraft_carousel', __('Carousel', 'app-craft'))
                ->setup_labels($employees_labels)
                ->add_fields(array(
                    Field::make('image', 'image', __('Cover Image', 'app-craft'))->set_width(15)->set_value_type('url'),
                    Field::make('text', 'title', __('Title', 'app-craft'))->set_width(25),
                    Field::make('select', 'link_type', __('Link Type', 'app-craft'))
                        ->add_options(array(
                            'post' => __('Post', 'app-craft'),
                            'category' => __('Category', 'app-craft'),
                            'tag' => __('Tag', 'app-craft'),
                            'page' => __('Page', 'app-craft'),
                            'link' => __('Link', 'app-craft'),
                        ))
                        ->set_width(15),
                    Field::make('text', 'link_url', __('Link URL', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'link',
                            ),
                        ))
                        ->set_width(20),

                    Field::make('text', 'id', __('ID', 'app-craft'))
                        ->set_conditional_logic(array(
                            'relation' => 'OR',
                            array(
                                'field' => 'link_type',
                                'value' => array('post', 'category', 'tag', 'page'),
                                'compare' => 'IN',
                            ),
                        ))
                        ->set_help_text('Input a ID')
                        ->set_width(15),
                    // Field::make('checkbox', 'enabled', __('Enabled', 'app-craft'))
                    //     ->set_width(15),
                )),
        ))
        ->add_tab(__('Featured Posts', 'app-craft'), array(
            Field::make('complex', 'appcraft_sticky', __('Featured Posts', 'app-craft'))
                ->setup_labels($employees_labels)
                ->add_fields(array(
                    Field::make('text', 'title_before', __('Before Title', 'app-craft'))->set_width(25),
                    Field::make('text', 'title', __('Title', 'app-craft'))->set_width(25),
                    Field::make('select', 'link_type', __('Link Type', 'app-craft'))
                        ->add_options(array(
                            'post' => __('Post', 'app-craft'),
                            'category' => __('Category', 'app-craft'),
                            'tag' => __('Tag', 'app-craft'),
                            'page' => __('Page', 'app-craft'),
                            'link' => __('Link', 'app-craft'),
                        ))
                        ->set_width(15),
                    Field::make('text', 'link_url', __('Link URL', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'link',
                            ),
                        ))
                        ->set_width(20),

                    Field::make('text', 'id', __('ID', 'app-craft'))
                        ->set_conditional_logic(array(
                            'relation' => 'OR',
                            array(
                                'field' => 'link_type',
                                'value' => array('post', 'category', 'tag', 'page'),
                                'compare' => 'IN',
                            ),
                        ))
                        ->set_help_text('Input a ID')
                        ->set_width(15),
                    // Field::make('checkbox', 'enabled', __('Enabled', 'app-craft'))
                    //     ->set_width(15),
                )),
        ))
        ->add_tab(__('Grid Navigation', 'app-craft'), array(
            Field::make('complex', 'appcraft_featured', __('Grid Navigation', 'app-craft'))
                ->setup_labels($employees_labels)
                ->add_fields(array(
                    Field::make('image', 'image', __('Icon', 'app-craft'))->set_width(15)->set_value_type('url'),
                    Field::make('text', 'i18nkey', __('I18nKey', 'app-craft'))->set_width(15),
                    Field::make('text', 'title', __('Title', 'app-craft'))->set_width(15),

                    Field::make('select', 'link_type', __('Link Type', 'app-craft'))
                        ->add_options(array(
                            'post' => __('Post', 'app-craft'),
                            'category' => __('Category', 'app-craft'),
                            'tag' => __('Tag', 'app-craft'),
                            'page' => __('Page', 'app-craft'),
                            'link' => __('Link', 'app-craft'),
                        ))
                        ->set_width(15),
                    Field::make('text', 'link_url', __('Link URL', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'link',
                            ),
                        ))
                        ->set_width(20),

                    Field::make('text', 'id', __('ID', 'app-craft'))
                        ->set_conditional_logic(array(
                            'relation' => 'OR',
                            array(
                                'field' => 'link_type',
                                'value' => array('post', 'category', 'tag', 'page'),
                                'compare' => 'IN',
                            ),
                        ))
                        ->set_help_text('Input a ID')
                        ->set_width(15),
                    // Field::make('checkbox', 'enabled', __('Enabled', 'app-craft'))
                    //     ->set_width(15),
                )),
        ))
        ->add_tab(__('My page Menu', 'app-craft'), array(
            Field::make('complex', 'appcraft_two_menu', __('My page Two-Column Menu', 'app-craft'))
                ->setup_labels($employees_labels)
                ->add_fields(array(
                    Field::make('text', 'i18nkey', __('I18nKey', 'app-craft'))->set_width(15),
                    Field::make('text', 'title', __('Title', 'app-craft'))->set_width(15),

                    Field::make('text', 'icon', __('Icon', 'app-craft'))->set_width(15),
                    Field::make('color', 'color', __('Icon Color', 'app-craft'))->set_width(15),


                    Field::make('select', 'link_type', __('Link Type', 'app-craft'))
                        ->add_options(array(
                            'post' => __('Post', 'app-craft'),
                            'category' => __('Category', 'app-craft'),
                            'tag' => __('Tag', 'app-craft'),
                            'page' => __('Page', 'app-craft'),
                            'link' => __('Link', 'app-craft'),
                            'copy' => __('Copy', 'app-craft'),
                            'path' => __('APP Page', 'app-craft'),
                        ))
                        ->set_width(15),
                    Field::make('text', 'link_url', __('Link URL', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'link',
                            ),
                        ))
                        ->set_width(20),
                         Field::make('text', 'path', __('Link URL', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'path',
                            ),
                        ))
                        ->set_width(20),
                    Field::make('text', 'copy_text', __('Copy Text', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'copy',
                            ),
                        ))
                        ->set_width(20),

                    Field::make('text', 'id', __('ID', 'app-craft'))
                        ->set_conditional_logic(array(
                            'relation' => 'OR',
                            array(
                                'field' => 'link_type',
                                'value' => array('post', 'category', 'tag', 'page'),
                                'compare' => 'IN',
                            ),
                        ))
                        ->set_help_text('Input a ID')
                        ->set_width(15),
                    // Field::make('checkbox', 'enabled', __('Enabled', 'app-craft'))
                    //     ->set_width(15),
                )),
            Field::make('complex', 'appcraft_menu', __('My page List Menu', 'app-craft'))
                ->setup_labels($employees_labels)
                ->add_fields(array(
                    Field::make('text', 'i18nkey', __('I18nKey', 'app-craft'))->set_width(15),
                    Field::make('text', 'title', __('Title', 'app-craft'))->set_width(15),

                    Field::make('text', 'icon', __('Icon', 'app-craft'))->set_width(15),
                    Field::make('color', 'color', __('Icon Color', 'app-craft'))->set_width(15),


                    Field::make('select', 'link_type', __('Link Type', 'app-craft'))
                        ->add_options(array(
                            'post' => __('Post', 'app-craft'),
                            'category' => __('Category', 'app-craft'),
                            'tag' => __('Tag', 'app-craft'),
                            'page' => __('Page', 'app-craft'),
                            'link' => __('Link', 'app-craft'),
                            'copy' => __('Copy', 'app-craft'),
                        ))
                        ->set_width(15),
                    Field::make('text', 'link_url', __('Link URL', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'link',
                            ),
                        ))
                        ->set_width(20),
                    Field::make('text', 'copy_text', __('Copy Text', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'copy',
                            ),
                        ))
                        ->set_width(20),

                    Field::make('text', 'id', __('ID', 'app-craft'))
                        ->set_conditional_logic(array(
                            'relation' => 'OR',
                            array(
                                'field' => 'link_type',
                                'value' => array('post', 'category', 'tag', 'page'),
                                'compare' => 'IN',
                            ),
                        ))
                        ->set_help_text('Input a ID')
                        ->set_width(15),
                    // Field::make('checkbox', 'enabled', __('Enabled', 'app-craft'))
                    //     ->set_width(15),
                )),


        ))
        ->add_tab(__('Credit Tasks', 'app-craft'), array(
    Field::make('complex', 'appcraft_tasks', __('Credit Tasks', 'app-craft'))
        ->add_fields(array(
            Field::make('image', 'icon', __('Icon', 'app-craft'))->set_width(10)->set_value_type('url'),
            Field::make('color', 'bg_color', __('Background Color', 'app-craft'))->set_width(15),
            Field::make('text', 'title', __('Title', 'app-craft'))->set_width(15),
            Field::make('text', 'desc', __('Description', 'app-craft'))->set_width(15),
            Field::make('text', 'button_text', __('Button Text', 'app-craft'))->set_width(10),
            Field::make('select', 'link_type', __('Link Type', 'app-craft'))

                        ->add_options(array(
                            'post' => __('Post', 'app-craft'),
                            'category' => __('Category', 'app-craft'),
                            'tag' => __('Tag', 'app-craft'),
                            'page' => __('Page', 'app-craft'),
                            'link' => __('Link', 'app-craft'),
                            'copy' => __('Copy', 'app-craft'),
                            'path' => __('APP Page', 'app-craft'),
                        ))
                        ->set_width(15),
                    Field::make('text', 'link_url', __('Link URL', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'link',
                            ),
                        ))
                        ->set_width(15),
                    Field::make('text', 'path', __('Link URL', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'path',
                            ),
                        ))
                        ->set_width(15),
                    Field::make('text', 'copy_text', __('Copy Text', 'app-craft'))
                        ->set_conditional_logic(array(
                            array(
                                'field' => 'link_type',
                                'value' => 'copy',
                            ),
                        ))
                        ->set_width(15),

                    Field::make('text', 'id', __('ID', 'app-craft'))
                        ->set_conditional_logic(array(
                            'relation' => 'OR',
                            array(
                                'field' => 'link_type',
                                'value' => array('post', 'category', 'tag', 'page'),
                                'compare' => 'IN',
                            ),
                        ))
                        ->set_help_text('Input a ID')
                        ->set_width(15),
                ))
        ));
}
