<?php

// 获取插件的绝对路径
$plugin_directory = plugin_dir_path( dirname( __FILE__ ) );

// 引入其他 PHP 文件
include_once $plugin_directory . 'fields/basic-settings.php';
include_once $plugin_directory . 'fields/extension-settings.php';

function appcraft_settings_init() {
    add_action('carbon_fields_register_fields', 'appcraft_register_basic_fields',2);
    add_action('carbon_fields_register_fields', 'appcraft_register_extension_fields',3);
    add_action('rest_api_init', 'appcraft_register_api_hooks');
}

add_action('plugins_loaded', 'appcraft_settings_init');
