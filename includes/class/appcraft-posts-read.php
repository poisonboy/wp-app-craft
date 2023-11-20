<?php
function appcraft_pay_for_article($request) {
    $article_id = (int) $request['id'];
    $user_id = verify_user_token($request);
    // 检查用户是否已支付
    $has_payed = get_user_meta($user_id, '_appcraft_paid_points_' . $article_id, true)  ==  'payed' ;

    if ($has_payed) {
        return ['success' => false, 'message' => __('You have already unlocked this content.', 'app-craft')];
        
        }
    $pay_points = get_post_meta($article_id, '_appcraft_pay_points', true);
    $pay_points = abs(intval($pay_points));  

    // 扣除积分
    $result = appcraft_manage_points($user_id, $pay_points, sprintf(__('Paid content for reading article with ID %s', 'app-craft'), $article_id), 'subtract', $article_id);
    if (is_wp_error($result)) {
        return $result;  
    }

    // 设置已支付状态
    update_user_meta($user_id, '_appcraft_paid_points_' . $article_id, 'payed');
    $paid_content = get_post_meta($article_id, '_appcraft_paid_content', true);
     
    return [
        'success' => true, 
        'message' => __('Points deduction successful, you can now read the article.', 'app-craft'), 
        'has_payed' => true,
        'paid_content' => $paid_content
    ];
    }


function appcraft_reward_for_article($request) {
    $article_id = (int) $request['id'];
    $user_id = verify_user_token($request);

    $reward_range = get_post_meta($article_id, '_appcraft_reward_points', true);
    $reward_points = get_random_points_from_range($reward_range); 

    // 增加积分
    $result = appcraft_manage_points($user_id, $reward_points, sprintf(__('Earned reward for reading article with ID %s', 'app-craft'), $article_id), 'add', $article_id);
    if (is_wp_error($result)) {
        return $result;  
    }

    // 设置已获得奖励状态
    update_user_meta($user_id, '_appcraft_read_earned_' . $article_id, 'earned');
 
    return [
        'success' => true, 
        'message' => sprintf(__('Points reward successful, you have earned %s points, thank you for your reading.', 'app-craft'), $reward_points), 
        'has_earned' => true, 
        'reward_points' => $reward_points
    ];
    
}
function get_random_points_from_range($range) { 
    if (strpos($range, '-') === false) { 
        return abs(intval($range));
    }

    // 解析范围字符串，例如 "1-10"
    list($min, $max) = explode('-', $range);
    $min = abs(intval($min));
    $max = abs(intval($max));

    // 确保最小值小于等于最大值
    if ($min > $max) {
        list($min, $max) = array($max, $min);
    }

    // 返回随机数
    return rand($min, $max);
}
