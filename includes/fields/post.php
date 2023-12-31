<?php
defined('ABSPATH') or die('Direct file access not allowed');

use Carbon_Fields\Container;
use Carbon_Fields\Field;

function appcraft_add_article_fields()
{
    Container::make('post_meta', __('More Settings', 'wp-app-craft'))
        ->where('post_type', '=', 'post')
        ->set_priority('high')
        ->add_fields(array(
            Field::make('select', 'appcraft_read_type', __('Reading Type', 'wp-app-craft'))
                ->set_options(array(
                    'none' => __('Select Reading Type', 'wp-app-craft'),
                    'reward' => __('Reward', 'wp-app-craft'),
                    'pay' => __('Pay', 'wp-app-craft'),
                ))
                ->set_width(50),

            Field::make('text', 'appcraft_reward_points', __('Reward Points Range', 'wp-app-craft'))
                ->set_attribute('placeholder', 'e.g.: 1-10')
                ->help_text(__('Please enter reward points, e.g., 10; for random, enter the reward points range, e.g., 1-10.', 'wp-app-craft'))
                ->set_width(50)
                ->set_conditional_logic(array(
                    array(
                        'field' => 'appcraft_read_type',
                        'value' => 'reward',
                        'compare' => '=',
                    ),
                )),

            Field::make('text', 'appcraft_pay_points', __('Deduct Points', 'wp-app-craft'))
                ->set_attribute('type', 'number')
                ->set_width(50)
                ->set_conditional_logic(array(
                    array(
                        'field' => 'appcraft_read_type',
                        'value' => 'pay',
                        'compare' => '=',
                    ),
                )),

            Field::make('textarea', 'appcraft_paid_content', __('Paid Content', 'wp-app-craft'))
                ->help_text(__('Enter the paid content to be hidden. HTML code can be used to insert images and videos. It is recommended to use a 135 editor to edit the layout and style before copying the code.', 'wp-app-craft'))
                ->set_conditional_logic(array(
                    array(
                        'field' => 'appcraft_read_type',
                        'value' => 'pay',
                        'compare' => '=',
                    ),
                )),
        ));
}

add_action('carbon_fields_register_fields', 'appcraft_add_article_fields');
