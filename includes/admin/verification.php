<?php
 declare(strict_types=1);
function appcraft_cmp_do_output_buffer()
{
    ob_start();
}
add_action('init', 'appcraft_cmp_do_output_buffer');
// 检查IP请求限制
function check_ip_request_limit($ip) {
    $ip_key = "appcraft_ip_limit_{$ip}";
    $request_count = get_transient($ip_key);

    // 如果已经有这个IP的记录，并且请求次数超过了限制（例如5次）
    if (false !== $request_count && $request_count >= 5) {
        $expires_in = get_option('_transient_timeout_' . $ip_key) - time();
        return $expires_in;
    }

    // 如果没有记录或者还没有达到限制，增加计数器
    set_transient($ip_key, ($request_count === false ? 1 : $request_count + 1), 10 * MINUTE_IN_SECONDS);  // 保存10分钟
    return true;
}

 
function appcraft_check_validation()
{
    // delete_transient('appcraft_is_validated');

    if (isset($_GET['page']) && ($_GET['page'] == 'appcraft_basic_settings' || $_GET['page'] == 'appcraft_extension_settings')) {
        
  $is_validated = get_transient('appcraft_is_validated');
        if (!$is_validated) {
            wp_redirect(admin_url('admin.php?page=appcraft_validation_page'));
            exit;
        }
    }
}
add_action('admin_init', 'appcraft_check_validation');

 
function appcraft_add_validation_page()
{
    add_submenu_page(
        'admin.php', // 或其他有效的父级菜单 slug
        __('Validation', 'app-craft'),
        __('Validation', 'app-craft'),
        'manage_options',
        'appcraft_validation_page',
        'appcraft_render_validation_page'
    );
}
add_action('admin_menu', 'appcraft_add_validation_page');

 
function appcraft_render_validation_page()
{
    

?>
    <style>
        .Modal {
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -webkit-box-shadow: 0 5px 20px hsla(0, 0%, 7%, .1);
            box-shadow: 0 5px 20px hsla(0, 0%, 7%, .1);
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: column;
            flex-direction: column;

            margin-top: 24px;
            margin-right: 24px;
            max-height: calc(100vh - 48px);
            position: relative;
            -webkit-transition: max-height .8s ease;
            transition: max-height .8s ease;
            width: auto;
            z-index: 1;
        }

        .Modal-inner {
            background: #fff;
            border-radius: 2px;
            overflow: auto;
        }

        .Modal-content {
            -webkit-box-flex: 1;
            -ms-flex: 1 1;
            flex: 1 1;
            line-height: 1.7;
            margin: 0;
            opacity: 1;
            padding: 0;
        }

        .signFlowModal-container,
        .signQr-container {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            justify-content: center;
        }

        .signFlowModal-container {
            overflow: hidden;
            position: relative;
        }

        .signQr-container {
            background-color: #fff;
        }

        .signQr-leftContainer {
            width: 332px;
        }

        .Qrcode-container.smallVersion {
            padding-top: 98px;
        }

        .Qrcode-container {
            position: relative;
            text-align: left;
        }

        .css-k49mnn {
            box-sizing: border-box;
            margin: 0px;
            min-width: 0px;
            color: rgb(68, 68, 68);
            font-size: 16px;
            font-weight: 600;
            line-height: 23px;
        }

        .css-qj3urb {
            box-sizing: border-box;
            margin: 8px 0px 24px;
            min-width: 0px;
            color: rgb(68, 68, 68);
            font-size: 14px;
            line-height: 20px;
        }
        .css-x9rxz4 {
            box-sizing: border-box;
            margin: 24px 0px 0px;
            min-width: 0px;
            color: rgb(68, 68, 68);
            font-size: 14px;
            font-weight: 600;
            line-height: 20px;
        } .css-1o2gsjy {
            box-sizing: border-box;
            margin: 0px;
            padding-top: 98px;
            min-width: 0px;
            background-color: rgb(255, 255, 255);
            width: 400px;
            overflow: hidden;
            box-shadow: none;
        }
        .Qrcode-container.smallVersion .Qrcode-img {
            margin-bottom: 40px;
            margin-top: 40px;
        }

        .Qrcode-container.smallVersion .Qrcode-img {
            height: 120px;
            width: 120px;
        }

        .Qrcode-container.smallVersion .Qrcode-qrcode {
            border-radius: 6px;
            height: 120px;
            width: 120px;
            display: block;
        }

        .Qrcode-container.smallVersion .Qrcode-qrcode {
            border: 1px solid #ebebeb;
            padding: 8px;
        }

       
        .signQr-rightContainer {
            border-left: 1px solid #ebebeb;
        }

       

        .SignContainer-content {
            margin: 0 auto;

        }

        .SignContainer-inner {
            overflow: hidden;
            position: relative;
        }

        .Login-content {
            padding: 0 24px 30px;
        }

        .SignFlow {
            overflow: hidden;
        }

        .SignFlow-tabs {
            margin-top: 16px;
            text-align: left;
        }

        .SignFlow-tab--active {
            font-synthesis: style;
            font-weight: 600;
        }

        .SignFlow-tab--active {
            color: #121212;
            position: relative;
        }

        .SignFlow-tab {
            cursor: pointer;
            font-size: 16px;
            height: 49px;
            line-height: 46px;
            margin-right: 24px;
        }

        .SignFlow-tab {
            color: #444;
            display: inline-block;
        }

        .SignFlow-tab--active:after {
            background-color: #056de8;
            bottom: 0;
            content: "";
            display: block;
            height: 3px;
            position: absolute;
            width: 100%;
        }

        .Login-content .SignFlow-smsInputContainer {
            margin-top: 11px;
        }

        .Login-content .SignFlow-smsInputContainer {
            border-bottom: 1px solid #ebebeb;
        }

        .SignFlow-smsInputContainer {
            margin-top: 12px;
            position: relative;
        }

        .SignFlow .SignFlow-accountInput,
        .SignFlow .SignFlow-smsInput {
            width: auto;
        }

        .SignFlowInput {
            -webkit-box-flex: 1;
            -ms-flex: 1 1;
            flex: 1 1;
            position: relative;
        }

        .SignContainer-content .Input-wrapper {
            border: none;
            border-radius: 0;
            height: 44px;
            padding: 0;
            width: 100%;
            color: #8590a6;
        }

        .SignFlowInput input.Input {
            height: 48px;
            border: none;
        }

        .Login-content .username-input {
            color: #444;
        }

        input.i7cW1UcwT6ThdhTakqFm {

            line-height: 24px;
        }

        .Button--primary.Button--blue {
            background-color: #056de8;
            color: #fff;
            border: none;
            border-radius: 4px;
        }

        .SignFlow-submitButton {
            height: 36px;
            margin-top: 30px;
            width: 100%;
        }

        .SignContainer-tip {
            font-size: 12px;
            line-height: 19px;
        }

        .SignContainer-tip {
            color: #999;
            padding: 12px 24px 30px;
        }
    </style>
    <div class="Modal  " tabindex="0">
        <div class="Modal-inner">
            <div class="Modal-content">
                <div>
                    <div class="signFlowModal-container">
                        <div class="signQr-container">
                            <div class="signQr-leftContainer">
                                <div class="Qrcode-container smallVersion">
                                    <div class="css-k49mnn">第1步：打开微信</div>
                                    <div class="css-qj3urb">扫码关注公众号，发送‘验证码’获取邀请码</div>
                                    <div class="Qrcode-content">
                                        <div class="Qrcode-img">
                                            <img class="Qrcode-qrcode" width="150" height="150" src="/wp-content/plugins/app-craft/assets/images/qrcode.jpg" alt="二维码">
                                        </div>
                                        <div class="Qrcode-guide-message">
                                            <div class="css-x9rxz4"> 或者微信搜索‘微慕’</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="signQr-rightContainer">
                                <div class="css-1o2gsjy">
                                    <div class="SignContainer-content">
                                        <div class="SignContainer-inner">
                                            <form method="POST" class="SignFlow Login-content">
                                                <div class="css-k49mnn">第2步：验证</div>
                                                <div class="SignFlow-tabs">
                                                    <div class="SignFlow-tab SignFlow-tab--active" role="button" tabindex="0">验证码</div>
                                                </div>
                                                <div class="SignFlow SignFlow-smsInputContainer">
                                                    <div class="SignFlowInput SignFlow-smsInput">
                                                        <label class="Input-wrapper ">
                                                            |<input name="appcraft_verification_code" type="number" class="Input  username-input" placeholder="输入 6 位验证码" value="">

                                                             
                                                        </label>
                                                    </div>
                                                </div>

                                                <button type="submit" name="appcraft_verify" class="Button SignFlow-submitButton  Button--primary Button--blue ">
                                                    立即认证
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="SignContainer-tip">认证后可以访问基本设置和扩展设置页面，如有问题可以联系客服微信：poisonkid</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php 
if (isset($_POST['appcraft_verify'])) {
   $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);// 获取请求的IP地址

 $limit_check = check_ip_request_limit($ip);
if ($limit_check !== true) {
    if ($limit_check > 60) {
        $time_left = round($limit_check / 60) . ' 分钟';
    } else {
        $time_left = $limit_check . ' 秒';
    }
    echo '<p>请求次数过多，请在 ' . $time_left . ' 后再试。</p>';
    return;
}

   if (!isset($_POST['appcraft_verification_code'])) {
    echo '<p>Verification code is missing.</p>';
    return;
}

$code = sanitize_textarea_field($_POST['appcraft_verification_code']);
$site_url = get_site_url();
$admin_email = get_option('admin_email');
$args = [
    'body' => json_encode([
        'code' => $code,
        'site_url' => $site_url,
        'admin_email' => $admin_email
    ]),
    'headers' => ['Content-Type' => 'application/json']
];

$response = wp_remote_post('https://xsy.xcxgy.cn/wp-json/app-craft/v1/validatecode', $args);

if (is_wp_error($response)) {
    echo '<p>Error: ' . $response->get_error_message() . '</p>';
    return;
}

if (wp_remote_retrieve_response_code($response) !== 200) {
    echo '<p>请求无法发送，请填写正确验证码.</p>';
    return;
}

$body = wp_remote_retrieve_body($response);
try {
    $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    echo '<p>JSON 解析错误: ' . $e->getMessage() . '</p>';
    return;
}

if ($json['status'] === 'success') {
    set_transient('appcraft_is_validated', true, 7 * 24 * 60 * 60);
    wp_redirect(admin_url('admin.php?page=appcraft_basic_settings'));
    exit;
} else {
    echo '<p>验证码有误，请重新输入</p>';
}

}

}
 
function appcraft_add_plugin_page_settings_link(array $links): array {
    $is_validated = get_transient('appcraft_is_validated');

    if ($is_validated) {
        $settings_link = '<a href="admin.php?page=appcraft_basic_settings">' . __('Basic Settings', 'app-craft') . '</a>';
        $extension_settings_link = '<a href="admin.php?page=appcraft_extension_settings">' . __('Extension Settings', 'app-craft') . '</a>';
        array_unshift($links, $settings_link, $extension_settings_link);
    } else {
        $validation_link = '<a href="admin.php?page=appcraft_validation_page">' . __('Validation Required', 'app-craft') . '</a>';
        array_unshift($links, $validation_link);
    }

    return $links;
}
add_filter('plugin_action_links_app-craft/app-craft.php', 'appcraft_add_plugin_page_settings_link');
 
