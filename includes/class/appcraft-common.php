<?php
function appcraft_get_vip_levels() {
    $vip_levels = carbon_get_theme_option('appcraft_vip_levels');
    $options = array('0' => 'Not VIP');

    if (is_array($vip_levels)) {
        foreach ($vip_levels as $index => $level) {
            $options[$index + 1] = $level['alias'];
        }
    }

    return $options;
}

