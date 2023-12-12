<?php
defined('ABSPATH') or die('Direct file access not allowed');




function appcraft_format_response($data)
{
    $response = array(
        'code' => 200,
        'data' => $data,

    );
    return new WP_REST_Response($response, 200);
}

function appcraft_get_basic_settings()
{
    $data = array(
        'app_name' => carbon_get_theme_option('appcraft_app_name'),
        'app_intro' => carbon_get_theme_option('appcraft_app_intro'),
        'app_copyright' => carbon_get_theme_option('appcraft_mypage'),
        'app_privacy_policy' => carbon_get_theme_option('appcraft_privacy_policy'),
        'app_user_agreement' => carbon_get_theme_option('appcraft_user_agreement'),
        'app_icon' => carbon_get_theme_option('appcraft_app_icon'),
        'app_logo' => carbon_get_theme_option('appcraft_app_logo'),
        'app_share_cover' => carbon_get_theme_option('appcraft_app_share_cover'),
        'app_default_thumb' => carbon_get_theme_option('appcraft_app_default_thumb'),
        'app_default_avatar' => carbon_get_theme_option('appcraft_app_default_avatar'),
        'copyright_statement' => carbon_get_theme_option('appcraft_copyright_statement'),
        'home_categories' => carbon_get_theme_option('appcraft_home_categories'),
        'cat_categories' => carbon_get_theme_option('appcraft_cat_categories'),
        'rand_categories' => carbon_get_theme_option('appcraft_rand_categories'),
        'comment_moderation' => carbon_get_theme_option('appcraft_comment_moderation'),
        'email_verify' => carbon_get_theme_option('appcraft_email_verify'),
        'one_click' => carbon_get_theme_option('enable_one_click_registration'),
        'points' => array(
            'initial_points' => carbon_get_theme_option('appcraft_initial_points'),
            'signin_points' => carbon_get_theme_option('appcraft_signin_points'),
            'seven_day_bonus' => carbon_get_theme_option('appcraft_signin_7day_points'),
            'points_for_invitation' => carbon_get_theme_option('appcraft_points_for_invitation'),
            'daily_limit' => carbon_get_theme_option('appcraft_daily_limit'),
        ),
        'version' => carbon_get_theme_option('appcraft_settings_version'),
    );
    return appcraft_format_response($data);
}
function appcraft_get_extension_settings()
{
    $carousel = carbon_get_theme_option('appcraft_carousel');
    $sticky = carbon_get_theme_option('appcraft_sticky');
    $featured = carbon_get_theme_option('appcraft_featured');

    $two_menu = carbon_get_theme_option('appcraft_two_menu');
    $list_menu = carbon_get_theme_option('appcraft_menu');

    $data = array(
        'carousel' => $carousel,
        'sticky' => $sticky,
        'featured' => $featured,

        'two_menu' => $two_menu,
        'list_menu' => $list_menu,
        'tasks' => carbon_get_theme_option('appcraft_tasks'),
        'version' => carbon_get_theme_option('appcraft_settings_version'),
    );
    return appcraft_format_response($data);
}

function appcraft_get_ad_settings()
{
    $data = array(
        'custom_ads' => carbon_get_theme_option('appcraft_custom_ads'),
        'rewarded_ad_id' => carbon_get_theme_option('appcraft_rewarded_ad_id'),
        'native_ad_id' => carbon_get_theme_option('appcraft_native_ad_id'),
        'interstitial_ad_id' => carbon_get_theme_option('appcraft_interstitial_ad_id'),
        'post_list_enabled' => carbon_get_theme_option('appcraft_post_list_enabled'),
        'categories_page_enabled' => carbon_get_theme_option('appcraft_categories_page_enabled'),
        'post_details_enabled' => carbon_get_theme_option('appcraft_post_details_enabled'),
        'version' => carbon_get_theme_option('appcraft_settings_version'),
    );
    return appcraft_format_response($data);
}
