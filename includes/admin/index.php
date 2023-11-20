<?php
function appcraft_create_menu()
{
    // 创建一个顶级菜单
    add_menu_page(
        __('AppCraft', 'wp-app-craft'), // 页面标题
        __('AppCraft', 'wp-app-craft'), // 菜单标题
        'manage_options', // 权限
        'appcraftbuilder', // 菜单slug
        'appcraft_settings_page', // 显示页面的函数
        plugins_url('wp-app-craft/assets/images/app.svg'), // 图标URL

    );
}

function appcraft_settings_page()
{
    // 开始输出缓冲
    ob_start();
?>
    <style>
        .panel {

            position: relative;
            background: #fff;
            box-shadow: rgba(27, 26, 49, 0.05) 0px 12px 28px 0;
            border-radius: 6px;
            margin: 60px 20px 0 0;
            padding: 20px;
        }

        .title {
            padding: 10px 0 15px;

            margin-bottom: 15px;
        }

        .title .panel-title {
            margin-bottom: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .panel-details {
            margin-left: -3%;
            padding: 0 20px 20px;
        }

        .panel-details .panel-detail {
            display: inline-block;
            width: 39%;
            margin-left: 2%;
            margin-right: -4px;
            vertical-align: top;
        }

        .panel-details .panel-detail.image {
            width: 26%;
            margin-left: 1%;
        }

        .general {
            width: 30% !important;
        }

        .panel-detail.image img {
            width: 100%;
        }

        .panel-detail .dashicons {
            margin-right: 5px;
        }

        .panel-detail .content {
            color: #505050;
            line-height: 1.8;
        }

        .panel-detail .content {
            color: #505050;
            line-height: 1.8;
            font-size: 14px;
        }

        .panel-detail .title {
            margin-bottom: 10px;
        }

        .demo {
            margin-bottom: 30px;
        }

        .panel-details .panel-detail.image figure {
            margin: 0;
        }

        .general-content.content {
            margin-bottom: 15px;
        }

        .button {
            border-color: #1c6ef3 !important;
            color: #588ff4 !important;
        }

        .button.button-primary.button-hero {
            background: #1c6ef3;
            border-color: #588ff4;
            color: #fff !important;
            margin-right: 20px;
            margin-bottom: 15px;
        }

        .updating-message::before {
            margin: 11px 5px 0 -2px;
        }
    </style>
    <div class="panel ">

        <div class="title">
            <h2 class="panel-title"><?php echo __('AppCraft', 'wp-app-craft'); ?> </h2>
            <p class="panel-description">
                <?php echo __('AppCraft', 'wp-app-craft'); ?><?php echo __(' plugin has been installed. You can start using it now. Next, you can do some basic settings and advanced settings, then download the front-end source code for integration. Please refer to', 'wp-app-craft'); ?>
                <a href="https://github.com/poisonboy/wordpress-to-appcraft/wiki" target="_blank"><?php echo __('AppCraft', 'wp-app-craft'); ?> </a> <?php echo __('Documentation', 'wp-app-craft'); ?><?php echo __('for detailed operations', 'wp-app-craft'); ?>.
            </p>
        </div>

        <div class="panel-details">

            <div class="panel-detail image">
                <figure> <img src="/wp-content/plugins/wp-app-craft/assets/images/screenshot.png"> </figure>
            </div>

            <div class="panel-detail general">
                <div class="general-info">
                    <h2 class="general-title title"><span class="dashicons dashicons-thumbs-up"></span>
                        <?php echo __('AppCraft', 'wp-app-craft'); ?>
                    </h2>
                    <div class="general-content content">
                        <?php echo __('AppCraft', 'wp-app-craft'); ?> <?php echo __('Can seamlessly integrate WordPress with appcraft. It allows WordPress site owners to generate their own mini programs and APPs without hiring technical personnel, learning programming skills, or paying fees.', 'wp-app-craft'); ?>

                    </div>
                    <div class="general-info-links">
                        <div class="buttons">
                            <a class=" button button-hero  button-primary" href="https://github.com/poisonboy/wordpress-to-appcraft/wiki">
                                <?php echo __('View Documentation', 'wp-app-craft'); ?>
                            </a>
                            <a class="button button-hero" href="https://github.com/poisonboy/wordpress-to-appcraft">
                                <?php echo __('Download Source Code', 'wp-app-craft'); ?>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <div class="panel-detail ">
                <div>
                    <div class="demo">
                        <h2 class="  title"><span class="dashicons dashicons-carrot "></span>
                            <?php echo __('Case Studies', 'wp-app-craft'); ?></h2>
                        <div class="  content">
                            <?php echo __('If you generate mini programs or APPs using this plugin, you can submit them to us as case studies for display.', 'wp-app-craft'); ?>
                            <a target="_blank" href="https://github.com/poisonboy/wordpress-to-appcraft/wiki/demos">
                                <?php echo __('View Now', 'wp-app-craft'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="document">
                        <h2 class="  title"><span class="dashicons dashicons-coffee"></span>
                            <?php echo __('Documentation', 'wp-app-craft'); ?>
                        </h2>
                        <div class="  content">
                            <?php echo __('You can follow the documentation step-by-step without any coding knowledge. It takes about an hour to go from installing the plugin to generating the app.。', 'wp-app-craft'); ?>
                            <a target="_blank" href="https://github.com/poisonboy/wordpress-to-appcraft/wiki">
                                <?php echo __('View Documentation', 'wp-app-craft'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
<?php
    // 结束输出缓冲并获取内容
    $output = ob_get_clean();

    // 输出内容
    echo $output;
}
