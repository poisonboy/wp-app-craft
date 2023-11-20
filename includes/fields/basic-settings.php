<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;
 
function appcraft_register_basic_fields()
{
    $employees_labels = array(
        'plural_name' => __('Items', 'app-craft'),
        'singular_name' => __('Item', 'app-craft'),
    );
    // 创建基本设置子菜单
     
    $basic_settings = Container::make('theme_options', __('Basic Settings', 'app-craft'))
        ->set_page_parent('appcraftbuilder') // 设置父菜单的slug
        ->set_page_file('appcraft_basic_settings') //
        // ->set_page_menu_title('微慕自定义')
        ->add_tab(__('Application', 'app-craft'), array(
            Field::make('text', 'appcraft_app_name', __('Application Name', 'app-craft'))->set_classes('w-30'),
            Field::make('text', 'appcraft_app_intro', __('Application Introduction', 'app-craft'))->set_classes('w-30'),
            Field::make('text', 'appcraft_user_agreement', __('Terms of Service', 'app-craft'))->set_classes('w-30'),
            Field::make('text', 'appcraft_privacy_policy', __('Privacy Policy', 'app-craft'))->set_classes('w-30'),
            Field::make('textarea', 'appcraft_copyright_statement', __('Copyright Statement', 'app-craft'))->set_classes('w-30'),
            Field::make('textarea', 'appcraft_mypage', __('MY Page Bottom', 'app-craft'))

                ->set_help_text(__('Display at the bottom of my page, can be copyright information, or terms of service and privacy policy links, support HTML rich text.', 'app-craft'))
                ->set_classes('w-30'),

            Field::make('image', 'appcraft_app_icon', __('Application Icon', 'app-craft'))->set_value_type('url'),
            Field::make('image', 'appcraft_app_logo', __('Application Logo', 'app-craft'))->set_value_type('url'),
            Field::make('image', 'appcraft_app_share_cover', __('Application Share Cover', 'app-craft'))->set_value_type('url'),
            Field::make('image', 'appcraft_app_default_thumb', __('Application Default Thumb', 'app-craft'))->set_value_type('url'),
            Field::make('image', 'appcraft_app_default_avatar', __('Application Default Avatar', 'app-craft'))->set_value_type('url'),

        ))
        
        
        ->add_tab(__('Display', 'app-craft'), array(
            Field::make('text', 'appcraft_home_categories', __('Homepage Categories', 'app-craft'))->set_classes('w-30'),
            Field::make('text', 'appcraft_cat_categories', __('Categories Page Categories', 'app-craft'))->set_classes('w-30'),
            Field::make('text', 'appcraft_rand_categories', __('Rand Posts Categories', 'app-craft'))->set_classes('w-30'),
        ))
        ->add_tab(__('Credit Settings', 'app-craft'), array(
    Field::make('text', 'appcraft_initial_points', __('Initial Points', 'app-craft'))
        ->set_default_value('20'),
    Field::make('text', 'appcraft_signin_points', __('Sign-in Points', 'app-craft'))
        ->set_default_value('5'),
    Field::make('text', 'appcraft_signin_7day_points', __('Continuous Sign-in 7 Days Extra Reward Points', 'app-craft'))
        ->set_default_value('30'),
    Field::make('text', 'appcraft_points_for_invitation', __('Invitation Points', 'app-craft'))
        ->set_default_value('10'),
    Field::make('text', 'appcraft_daily_limit', __('Daily Points Limit', 'app-craft'))
        ->set_default_value('100'),
))

        ->add_tab(__('Safe', 'app-craft'), array(
            Field::make('text', 'appcraft_jwt_secret_key', __('JWT Secret Key', 'app-craft'))
                ->set_classes('w-30')
                ->set_attribute('type', 'password'),
            Field::make('text', 'appcraft_jwt_expiration', __('JWT Expiration Time (hours)', 'app-craft'))
                ->set_classes('w-30')
                ->set_attribute('type', 'number')
                ->set_default_value(24),
            Field::make('text', 'appcraft_updateprofile_times', __('User Update Profile Times', 'app-craft'))
                ->set_classes('w-30')
                ->set_attribute('type', 'number')
                ->set_default_value(3),
            Field::make('text', 'appcraft_uploadavatar_times', __('User Upload Avartar Times', 'app-craft'))
                ->set_classes('w-30')
                ->set_attribute('type', 'number')
                ->set_default_value(3),
            Field::make('checkbox', 'appcraft_comment_moderation', __('Enable Comment Moderation', 'app-craft'))
                ->set_default_value('yes'),
            Field::make('text', 'appcraft_comment_time_limit', __('Comment Time Limit (minutes)', 'app-craft'))
                ->set_classes('w-30')
                ->set_attribute('type', 'number')
                ->set_default_value(15),
            Field::make('text', 'appcraft_max_comments', __('Max Comments Within Time Limit', 'app-craft'))
                ->set_classes('w-30')
                ->set_attribute('type', 'number')
                ->set_default_value(5),
           Field::make('checkbox', 'appcraft_email_verify', __('Enable Email Verify', 'app-craft'))
                ->set_default_value('yes'),
                 Field::make('text', 'appcraft_settings_version', __('version（auto）', 'app-craft'))
        ->set_default_value('0')->set_attribute('readOnly', 'readOnly'),  
        ))
      ;
     
}
add_action('carbon_fields_theme_options_container_saved', 'update_appcraft_settings_version');

function update_appcraft_settings_version() { 
    $current_version = carbon_get_theme_option('appcraft_settings_version');
    if (!$current_version) {
        $current_version = 0;  
    } 
    $new_version = $current_version + 1; 
    carbon_set_theme_option('appcraft_settings_version', $new_version);
}
