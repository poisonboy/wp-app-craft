<?php
/**
 * Plugin Name: AppCraft - WordPress to Uniapp  Integrator
 * Description: AppCraft seamlessly integrates WordPress with Uniapp , enabling the swift creation of mobile applications directly from your WordPress content.
 * Version: 1.0.0
 * Author: DomiUI
 * Author URI: https://github.com/poisonboy/wp-app-craft
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-wp-app-craft
 * Domain Path: /languages
 */


// Prevent direct file access
defined('ABSPATH') or die('Direct file access not allowed');

 
 
//   Use Carbon Fields
use Carbon_Fields\Container;
use Carbon_Fields\Field;

// Include other PHP files from the plugin 

include_once 'includes/admin/index.php';   
include_once 'includes/admin/settings.php'; 
include_once 'includes/admin/points.php';   
include_once 'includes/admin/verification.php';  
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

function appcraft_init() { 
require_once __DIR__ . '/vendor/autoload.php'; 
    \Carbon_Fields\Carbon_Fields::boot();
  
    add_action('admin_menu', 'appcraft_create_menu', 1);
}
register_activation_hook(__FILE__, 'appcraft_activate_plugin');

function appcraft_activate_plugin() {
    appcraft_create_points_table();
}

 
function load_appcraft_textdomain() {
    load_plugin_textdomain( 'wp-app-craft', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'load_appcraft_textdomain' );

function my_plugin_load_textdomain() {
    $plugin_rel_path = basename(dirname(__FILE__)) . '/vendor/htmlburger/carbon-fields/languages'; 
    load_plugin_textdomain( 'carbon-fields-ui', false, $plugin_rel_path );
}

add_action( 'plugins_loaded', 'my_plugin_load_textdomain' );
 
 

function add_custom_styles() {
  wp_enqueue_style( 'custom-style', plugins_url( 'assets/css/style.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'add_custom_styles' );