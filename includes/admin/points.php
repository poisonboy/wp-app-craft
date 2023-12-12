<?php
defined('ABSPATH') or die('Direct file access not allowed');

add_action('admin_menu', 'appcraft_add_submenu', 99);

// 添加子菜单
function appcraft_add_submenu()
{
    add_submenu_page(
        'appcraftbuilder',
        __('User Records', 'wp-app-craft'),
        __('Points Records', 'wp-app-craft'),
        'manage_options',
        'appcraft-user-records',
        'appcraft_display_user_records'
    );
}
function appcraft_create_points_table()
{
    global $wpdb;
    $table_name = sanitize_key($wpdb->prefix . 'appcraft_points_log');

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        event varchar(255) NOT NULL,
        type varchar(100) NOT NULL,
        points_earned mediumint(9) NOT NULL,
        current_points mediumint(9) NOT NULL,
        inviter_count mediumint(9) NOT NULL,
        inviter_id mediumint(9) NOT NULL, 
        article_id mediumint(9) DEFAULT NULL, 
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
function appcraft_build_points_query($table_name, $filter_user_id, $filter_username, $filter_inviter_id, $filter_event, $order_by, $order_dir)
{
    // 使用传递的参数来构建 SQL 查询
    $where = [];
    if ($filter_user_id) $where[] = "user_id = $filter_user_id";
    if ($filter_username) $where[] = "username LIKE '%$filter_username%'";
    if ($filter_inviter_id) $where[] = "inviter_id = $filter_inviter_id";
    if ($filter_event) $where[] = "event = '$filter_event'";

    if ($order_by == 'inviter_count') $where[] = "inviter_count > 0";

    $sql = "SELECT * FROM $table_name";
    if (!empty($where)) $sql .= " WHERE " . join(' AND ', $where);
    $sql .= " ORDER BY $order_by $order_dir";

    return $sql;
}

function appcraft_render_table_row($row)
{
    $user_info = get_userdata($row->user_id);
    $username = $user_info ? $user_info->nickname : __('[User Deleted]', 'wp-app-craft');

    $inviter_id_display = $row->inviter_id > 0 ? $row->inviter_id : '';
    $inviter_count_display = $row->inviter_count > 0 ? $row->inviter_count : '';

    return "<tr>
        <td>{$row->user_id}</td>
        <td>{$username}</td>
        <td>{$row->event}</td>
        <td>{$row->points_earned}</td>
        <td>$inviter_id_display</td>
        <td>{$row->time}</td>
        <td>{$row->current_points}</td>
        <td>$inviter_count_display</td>
    </tr>";
}
function appcraft_display_user_records()
{
    global $wpdb;
    $table_name = sanitize_key($wpdb->prefix . 'appcraft_points_log');
    // 分页参数
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $items_per_page = 20;
    // 获取筛选和排序参数
    $filter_user_id = isset($_GET['filter_user_id']) ? intval($_GET['filter_user_id']) : '';
    $filter_username = isset($_GET['filter_username']) ? sanitize_text_field($_GET['filter_username']) : '';
    $filter_inviter_id = isset($_GET['filter_inviter_id']) ? intval($_GET['filter_inviter_id']) : '';
    $filter_event = isset($_GET['filter_event']) ? sanitize_text_field($_GET['filter_event']) : '';
    $order_by = isset($_GET['order_by']) ? sanitize_text_field($_GET['order_by']) : 'id';
    $order_dir = isset($_GET['order_dir']) ? sanitize_text_field($_GET['order_dir']) : 'DESC';
    // 总记录数查询，确保表名已被清理
    $total_items_sql = "SELECT COUNT(*) FROM {$table_name}";
    // 添加筛选条件到 $total_items_sql 
    $total_items = $wpdb->get_var($total_items_sql);
    // 将参数传递给 appcraft_build_points_query
    $sql = appcraft_build_points_query($table_name, $filter_user_id, $filter_username, $filter_inviter_id, $filter_event, $order_by, $order_dir);
    // 分页 SQL 查询
    $offset = ($current_page - 1) * $items_per_page;
    $sql = appcraft_build_points_query($table_name, $filter_user_id, $filter_username, $filter_inviter_id, $filter_event, $order_by, $order_dir);
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);

    // 获取查询结果
    $results = $wpdb->get_results($sql);
?>
    <div class="wrap">
        <h1><?php esc_html_e('appcraft User Points Records', 'wp-app-craft'); ?></h1>
        <form method="GET" action="">
            <input type="hidden" name="page" value="appcraft-user-records">
            <label for="filter_user_id"><?php esc_html_e('User ID:', 'wp-app-craft'); ?></label>
            <input type="text" name="filter_user_id" value="<?php echo $filter_user_id; ?>">
            <label for="filter_username"><?php esc_html_e('Username:', 'wp-app-craft'); ?></label>
            <input type="text" name="filter_username" value="<?php echo $filter_username; ?>">
            <label for="filter_inviter_id"><?php esc_html_e('Inviter ID:', 'wp-app-craft'); ?></label>
            <input type="text" name="filter_inviter_id" value="<?php echo $filter_inviter_id; ?>">
            <label for="filter_event"><?php esc_html_e('Event:', 'wp-app-craft'); ?></label>
            <input type="text" name="filter_event" value="<?php echo $filter_event; ?>">
            <label for="order_by"><?php esc_html_e('Order By:', 'wp-app-craft'); ?></label>
            <select name="order_by">
                <option value="points_earned"><?php esc_html_e('Points Earned', 'wp-app-craft'); ?></option>
                <option value="current_points"><?php esc_html_e('Total Points at Event', 'wp-app-craft'); ?></option>
                <option value="inviter_count"><?php esc_html_e('Number of Invitations', 'wp-app-craft'); ?></option>
            </select>
            <label for="order_dir"><?php esc_html_e('Order Direction:', 'wp-app-craft'); ?></label>
            <select name="order_dir">
                <option value="ASC"><?php esc_html_e('Ascending', 'wp-app-craft'); ?></option>
                <option value="DESC"><?php esc_html_e('Descending', 'wp-app-craft'); ?></option>
            </select>
            <input type="submit" value="<?php esc_html_e('Apply', 'wp-app-craft'); ?>">
            <a href="?page=appcraft-user-records" class="button"><?php esc_html_e('Reset', 'wp-app-craft'); ?></a>
        </form>

        <table class="wp-list-table widefat fixed striped" style=" margin: 6px 0;">
            <thead>
                <tr>
                    <th><?php esc_html_e('User ID', 'wp-app-craft'); ?></th>
                    <th><?php esc_html_e('Username', 'wp-app-craft'); ?></th>
                    <th><?php esc_html_e('Points Event', 'wp-app-craft'); ?></th>
                    <th><?php esc_html_e('Points Earned', 'wp-app-craft'); ?></th>
                    <th><?php esc_html_e('Inviter ID', 'wp-app-craft'); ?></th>
                    <th><?php esc_html_e('Time', 'wp-app-craft'); ?></th>
                    <th><?php esc_html_e('Total Points at Event', 'wp-app-craft'); ?></th>
                    <th><?php esc_html_e('Invitations Count at Event', 'wp-app-craft'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($results as $row) {
                    echo appcraft_render_table_row($row);
                }
                ?>
            </tbody>
        </table>
        <?php
        // 分页链接
        $total_pages = ceil($total_items / $items_per_page);
        $page_links = paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '?paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'type' => 'plain'
        ));
        echo '<span class="displaying-num alignleft"  >' . $total_items . ' ' . __('items', 'wp-app-craft') . '</span>';
        echo '<div class="pagination alignright" >' . $page_links . '</div>';   ?>
    </div>

<?php


}
