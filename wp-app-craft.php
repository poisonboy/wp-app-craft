<?php

/**
 * Plugin Name: AppCraft - WordPress to Uniapp  Integrator
 * Description: AppCraft seamlessly integrates WordPress with Uniapp , enabling the swift creation of mobile applications directly from your WordPress content.
 * Version: 1.0.0
 * Author: DomiUI
 * Author URI: https://github.com/poisonboy/wp-app-craft
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-app-craft
 * Domain Path: /languages
 */


// Prevent direct file access
defined('ABSPATH') or die('Direct file access not allowed');
// 定义一些动作行为
define('AC_TYPE_ARTICLE_READ', 'article_read'); // 阅读文章
define('AC_TYPE_INVITATION', 'user_invited'); // 邀请用户
define('AC_TYPE_REGISTRATION', 'user_register'); // 注册
define('AC_TYPE_CHECK_IN', 'check_in');       // 签到 
define('AC_TYPE_CASHBACK', 'cashback');       // 返利
define('AC_TYPE_TOP_UP', 'top_up');           // 充值
define('AC_TYPE_PAY_CONTENT', 'content_paid'); // 付费内容
define('AC_TYPE_PAY', 'paid'); // 付费

 

// Include other PHP files from the plugin 

include_once 'includes/admin/index.php';
include_once 'includes/admin/settings.php';
include_once 'includes/admin/points.php';
include_once 'includes/admin/userlist.php';
include_once 'includes/fields/category.php';
include_once 'includes/fields/post.php';
include_once 'includes/fields/user.php';
include_once 'includes/class/appcraft-common.php';
include_once 'includes/class/appcraft-verify.php';
include_once 'includes/class/appcraft-tags.php';
include_once 'includes/class/appcraft-categories.php';
include_once 'includes/class/appcraft-user-log.php';
include_once 'includes/class/appcraft-user-register.php';
include_once 'includes/class/appcraft-user-login.php';
include_once 'includes/class/appcraft-user-wxlogin.php';
include_once 'includes/class/appcraft-user-profile.php';
include_once 'includes/class/appcraft-user-sign.php';
include_once 'includes/class/appcraft-user-management.php';
include_once 'includes/class/appcraft-posts.php';
include_once 'includes/class/appcraft-posts-fav.php';
include_once 'includes/class/appcraft-posts-read.php';
include_once 'includes/class/appcraft-points.php';
include_once 'includes/class/appcraft-search.php';
include_once 'includes/class/appcraft-comments.php';
include_once 'includes/class/appcraft-comment-create.php';
include_once 'includes/class/appcraft-pages.php';
include_once 'includes/class/appcraft-settings.php';
include_once 'includes/class/appcraft-ids.php';
include_once 'includes/class/appcraft-upload-avatar.php';
include_once 'includes/api/api.php';

add_action('plugins_loaded', 'appcraft_init');

function appcraft_init()
{
  require_once __DIR__ . '/vendor/autoload.php';
  \Carbon_Fields\Carbon_Fields::boot();

  add_action('admin_menu', 'appcraft_create_menu', 1);
}
register_activation_hook(__FILE__, 'appcraft_activate_plugin');

function appcraft_activate_plugin()
{
  appcraft_create_points_table();
}

function appcraft_load_textdomains() {
   
    load_plugin_textdomain('wp-app-craft', false, basename(dirname(__FILE__)) . '/languages/'); 
    $plugin_rel_path = basename(dirname(__FILE__)) . '/vendor/htmlburger/carbon-fields/languages';
    load_plugin_textdomain('carbon-fields-ui', false, $plugin_rel_path);
}

add_action('plugins_loaded', 'appcraft_load_textdomains');


function appcraft_add_custom_styles()
{
  wp_enqueue_style('custom-style', plugins_url('assets/css/style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'appcraft_add_custom_styles');
