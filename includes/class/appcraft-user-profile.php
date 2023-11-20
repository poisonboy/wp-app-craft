<?php



function appcraft_get_user_profile(WP_REST_Request $request)
{
    $user_id = verify_user_token($request);
    $today = date('Y-m-d');
    $last_signin_date = get_user_meta($user_id, 'last_signin_date', true);

    $consecutive_days = get_user_meta($user_id, 'consecutive_days', true) ?: 0;
    $total_points = get_user_meta($user_id, 'appcraft_user_points', true) ?: 0;
    $today_earned_points = get_user_meta($user_id, 'today_earned_points', true) ?: 0;


    $isSignedIn = ($last_signin_date === $today);


    $user = get_userdata($user_id);
    $profile = array(
        // "token" => $token,
        "id" => $user->ID,
        "userName" => $user->user_login,
        "nickname" => $user->nickname,
        "date" => $user->user_registered,
        "email" => $user->user_email,
        "roleId" => $user->roles[0],
        "roleName" => appcraft_translate_user_role($user->roles[0]),
        "avatar" => get_user_meta($user->ID, '_appcraft_avatar', true),
        "bio" => get_user_meta($user->ID, '_appcraft_bio', true),
        "update_count" => carbon_get_user_meta($user->ID, 'appcraft_update_count'),
        "upload_count" => carbon_get_user_meta($user->ID, 'appcraft_upload_count'),
        "inviter_count" => get_user_meta($user->ID, 'appcraft_inviter_count', true),
        'consecutive_days' => $consecutive_days,
        'total_points' => $total_points,
        'today_earned_points' => $today_earned_points,
        'isSignedIn' => $isSignedIn
    );

    return new WP_REST_Response(array('status' => 'success', 'data' => $profile), 200);
}

function appcraft_update_user_profile(WP_REST_Request $request)
{
    $user_id = verify_user_token($request);

    $update_count = carbon_get_user_meta($user_id, 'appcraft_update_count');
    if ($update_count-- <= 0) {
        return new WP_REST_Response(['status' => 'error', 'message' => __('Update limit reached', 'app-craft')], 403);
    }
    carbon_set_user_meta($user_id, 'appcraft_update_count', $update_count);

    foreach (['nickname', 'bio', 'avatar'] as $key) {
        if ($value = $request->get_param($key)) {
            $key === 'nickname' ? wp_update_user(['ID' => $user_id, 'nickname' => $value, 'nickname' => $value]) : update_user_meta($user_id, '_appcraft_' . $key, $value);
        }
    }

    if ($email = sanitize_email($request['email'])) {
        $code = $request['code'];
        $stored_code = get_transient("email_code_" . $email);
        if (is_null($code) || !$stored_code || $stored_code !== $code) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => is_null($code) ? __('Missing email verification code', 'app-craft') : (!$stored_code ? __('Verification code has expired', 'app-craft') : __('Incorrect verification code', 'app-craft')),
                400
            ]);
        }
        delete_transient("email_code_" . $email);
        wp_update_user(['ID' => $user_id, 'user_email' => $email]);
    }

    $updated_profile_response = appcraft_get_user_profile($request);
    $response_data = $updated_profile_response->get_data();
    $response_data['message'] = __('Profile updated successfully', 'app-craft');

    return new WP_REST_Response($response_data, 200);
}
