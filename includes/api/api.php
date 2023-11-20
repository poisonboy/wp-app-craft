<?php
function appcraft_register_api_hooks()
{
    // 获取基础设置
    register_rest_route('app-craft/v1', '/settings/basic', array(
        'methods' => 'GET',
        'callback' => 'get_basic_settings',

        'permission_callback' => '__return_true',
    ));
    // 获取扩展设置
    register_rest_route('app-craft/v1', '/settings/extension', array(
        'methods' => 'GET',
        'callback' => 'get_extension_settings',
        'permission_callback' => '__return_true',
    ));
    // 获取广告设置
    register_rest_route('app-craft/v1', '/settings/ad', array(
        'methods' => 'GET',
        'callback' => 'get_ad_settings',
        'permission_callback' => '__return_true',
    ));
    // 获取文章列表
    register_rest_route('app-craft/v1', '/posts', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_posts',
        'permission_callback' => '__return_true',
    ));
    // 获取随机文章
    register_rest_route('app-craft/v1', '/random-posts', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_random_posts',
        'permission_callback' => '__return_true',
    ));
    // 获取文章详情
    register_rest_route('app-craft/v1', '/posts/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'appcraft_get_post',
        'permission_callback' => '__return_true',
    ));
    // 获取文章积分
    register_rest_route('app-craft/v1', '/post_points/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'appcraft_get_post_points',
        'permission_callback' => 'verify_user_token',
    ));
    // 搜索文章

    register_rest_route('app-craft/v1', '/search', array(
        'methods' => 'GET',
        'callback' => 'appcraft_search_posts',
        'permission_callback' => '__return_true',
    ));
    // 收藏文章
    register_rest_route('app-craft/v1', '/add_fav', array(
        'methods' => 'POST',
        'callback' => 'appcraft_add_favorite',
        'permission_callback' => 'verify_user_token',
    ));
    // 取消收藏
    register_rest_route('app-craft/v1', '/remove_fav', array(
        'methods' => 'POST',
        'callback' => 'appcraft_remove_favorite',
        'permission_callback' => 'verify_user_token',
    ));
    // 收藏列表
    register_rest_route('app-craft/v1', '/get_fav', array(
        'methods' => 'POST',
        'callback' => 'appcraft_get_favorites',
        'permission_callback' => 'verify_user_token',
    ));
    // 积分记录
    register_rest_route('app-craft/v1', '/points_log', array(
        'methods' => 'POST',
        'callback' => 'appcraft_get_user_points_log',
        'permission_callback' => 'verify_user_token',
    ));
    // 邀请记录
    register_rest_route('app-craft/v1', '/invites_log', array(
        'methods' => 'POST',
        'callback' => 'appcraft_get_user_invites_log',
        'permission_callback' => 'verify_user_token',
    ));

    register_rest_route('app-craft/v1', '/article-rewarded/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_rewarded_articles_points',
        'permission_callback' => '__return_true',
    ));

    // 获取全站所有评论
    register_rest_route('app-craft/v1', '/comments', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_comments',
        'permission_callback' => '__return_true',
    ));
    // 获取某个文章评论列表
    register_rest_route('app-craft/v1', '/comments/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_comment',
        'permission_callback' => '__return_true',
    ));
    // 获取某个用户的评论列表
    register_rest_route('app-craft/v1', '/comments_by_user', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_comments_by_user',
        'permission_callback' => '__return_true',
    ));
    // 创建评论
    register_rest_route('app-craft/v1', '/comment', array(
        'methods' => 'POST',
        'callback' => 'appcraft_create_comment',
        'permission_callback' => '__return_true',
    ));

    // 获取分类列表
    register_rest_route('app-craft/v1', '/categories', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_categories',
        'permission_callback' => '__return_true',
    ));



    // 获取标签列表
    register_rest_route('app-craft/v1', '/tags', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_tags',
        'permission_callback' => '__return_true',
    ));

    // 获取标签详情
    register_rest_route('app-craft/v1', '/tags/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_tag',
        'permission_callback' => '__return_true',
    ));
    // 获取页面详情
    register_rest_route('app-craft/v1', '/pages/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'appcraft_get_page',
        'permission_callback' => '__return_true',
    ));

    //  文章付费阅读
    register_rest_route('app-craft/v1', '/pay_for_article', array(
        'methods' => 'POST',
        'callback' => 'appcraft_pay_for_article',
        'permission_callback' => 'verify_user_token',
    ));
    //  文章阅读奖励
    register_rest_route('app-craft/v1', '/reward_for_article', array(
        'methods' => 'POST',
        'callback' => 'appcraft_reward_for_article',
        'permission_callback' => 'verify_user_token',
    ));
    //  上传头像
    register_rest_route('app-craft/v1', '/upload-avatar', array(
        'methods' => 'POST',
        'callback' => 'appcraft_upload_user_avatar',
        'permission_callback' => 'verify_user_token',
    ));
    // 用户注册 
    register_rest_route('app-craft/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'appcraft_register_user',
        'permission_callback' => '__return_true',
    ));
    // 用户一键注册 
    register_rest_route('app-craft/v1', '/register-one-click', array(
        'methods' => 'POST',
        'callback' => 'one_click_register',
        'permission_callback' => '__return_true',
    ));

    // 用户登录

    register_rest_route('app-craft/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'appcraft_login_user',
        'permission_callback' => '__return_true',
    ));

    //  获取用户资料
    register_rest_route('app-craft/v1', '/get-profile', array(
        'methods' => 'POST',
        'callback' => 'appcraft_get_user_profile',
        'permission_callback' => 'verify_user_token',
    ));
    // 更新用户资料
    register_rest_route('app-craft/v1', '/update-profile', array(
        'methods' => 'POST',
        'callback' => 'appcraft_update_user_profile',
        'permission_callback' => 'verify_user_token',
    ));



    // 发送邮箱验证码 
    register_rest_route('app-craft/v1', '/send-email-code', array(
        'methods' => 'POST',
        'callback' => 'send_email_code',
        'permission_callback' => '__return_true',
    ));

    //  修改密码
    register_rest_route('app-craft/v1', '/update-password', array(
        'methods' => 'POST',
        'callback' => 'appcraft_update_password',
        'permission_callback' => 'verify_user_token',
    ));
    // 注销账号邮件
    register_rest_route('app-craft/v1', '/delete-account', array(
        'methods' => 'POST',
        'callback' => 'appcraft_send_delete_confirmation_email',
        'permission_callback' => 'verify_user_token',
    ));
    // 确认注销
    register_rest_route('app-craft/v1', '/confirm_delete', array(
        'methods' => 'POST',
        'callback' => 'appcraft_confirm_and_delete_account',
        'permission_callback' => '__return_true',
    ));
    // 创建订单
    register_rest_route('app-craft/v1', '/create-order', [
        'methods' => 'POST',
        'callback' => 'create_order_api_handler',
        'permission_callback' => 'verify_user_token',
    ]);
    // 微信登录
    register_rest_route('app-craft/v1', '/get-openid', array(
        'methods' => 'POST',
        'callback' => 'get_openid_from_code',
        'permission_callback' => '__return_true',
    ));


    // 用户签到
    register_rest_route('app-craft/v1', '/signin', array(
        'methods' => 'POST',
        'callback' => 'appcraft_signin_api',
        'permission_callback' => 'verify_user_token',
    ));
    // 用户购买VIP
    register_rest_route('app-craft/v1', '/buy-vip/', array(
        'methods' => 'POST',
        'callback' => 'appcraft_buy_vip_api',
        'permission_callback' => '__return_true',
    ));
}
