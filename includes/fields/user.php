<?php
defined('ABSPATH') or die('Direct file access not allowed');

use Carbon_Fields\Container;
use Carbon_Fields\Field;


function appcraft_add_user_fields()
{
    $vip_levels = appcraft_get_vip_levels();
    $updateprofile_times_default_value = carbon_get_theme_option('appcraft_updateprofile_times');
    $uploadavatar_times_default_value = carbon_get_theme_option('appcraft_uploadavatar_times');
    $updatephone_times_default_value = carbon_get_theme_option('appcraft_updatephone_times');
    Container::make('user_meta', __('User Settings', 'wp-app-craft'))
        ->add_fields(array(
            Field::make('image', 'appcraft_avatar', __('Avatar', 'wp-app-craft'))
                ->set_value_type('url'),

            Field::make('rich_text', 'appcraft_bio', __('Bio', 'wp-app-craft')),
            Field::make('text', 'appcraft_update_count', __('Profile Update Count', 'wp-app-craft'))->set_attribute('type', 'number')->set_default_value($updateprofile_times_default_value),
            Field::make('text', 'appcraft_upload_count', __('Avatar Upload Count', 'wp-app-craft'))->set_attribute('type', 'number')->set_default_value($uploadavatar_times_default_value),
            Field::make('text', 'appcraft_phone_update_count', __('Phone Update Count', 'wp-app-craft'))->set_attribute('type', 'number')->set_default_value($updatephone_times_default_value),

            Field::make('select', 'appcraft_vip_level', __('VIP Level', 'wp-app-craft'))
                ->add_options($vip_levels),
            Field::make('date', 'appcraft_vip_expiry', __('VIP Expiry Date', 'wp-app-craft')),

        ));
}
add_action('carbon_fields_register_fields', 'appcraft_add_user_fields', 15);
// 添加额外字段到用户资料页面
function appcraft_extra_user_profile_fields($user)
{   
    $user_points = get_user_meta($user->ID, 'appcraft_user_points', true);
    $user_points = $user_points ? $user_points : 0;
    $inviter_count = get_user_meta($user->ID, 'appcraft_inviter_count', true);
    $inviter_count = $inviter_count ? $inviter_count : 0;
    wp_nonce_field('update-user_' . $user->ID, 'user-profile-nonce');
 
?>

    <h3>Extra profile information</h3>

    <table class="form-table">
        <tr>
            <th><label for="appcraft-user-points">User Points</label></th>
            <td>
                <input type="number" name="appcraft_user_points" id="appcraft-user-points" value="<?php echo esc_attr($user_points); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="appcraft-inviter-count">Number of Invited Users</label></th>
            <td>
                <input type="text" name="appcraft_inviter_count" id="appcraft-inviter-count" value="<?php echo esc_attr($inviter_count); ?>" class="regular-text" readonly />
            </td>
        </tr>
    </table>
<?php
}

// 保存额外的用户资料字段
function appcraft_save_extra_user_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    // 验证 nonce
    if (!isset($_POST['user-profile-nonce']) || !wp_verify_nonce($_POST['user-profile-nonce'], 'update-user_' . $user_id)) {
        return false;
    }
    $appcraft_user_points = sanitize_text_field($_POST['appcraft_user_points']);
    if (is_numeric($appcraft_user_points)) {
        update_user_meta($user_id, 'appcraft_user_points', $appcraft_user_points);
    }
}

// 添加到WordPress后台
add_action('show_user_profile', 'appcraft_extra_user_profile_fields');
add_action('edit_user_profile', 'appcraft_extra_user_profile_fields');
add_action('personal_options_update', 'appcraft_save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'appcraft_save_extra_user_profile_fields');
