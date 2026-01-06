<?php
/**
 * Plugin Name: AI Scraper
 * Description: Plugin tự động scrape dữ liệu từ các website và đăng lại bài viết sau khi viết lại bằng AI.
 * Version: 1.4
 * Author: thao_dx@gmail.com
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Load các file class cần thiết
require_once plugin_dir_path(__FILE__) . 'includes/class-task-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-api-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-scheduler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-scrapper.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-logs.php';

$tdx_constants = require plugin_dir_path(__FILE__) . 'includes/constants.php';
define('TDX_CONST', $tdx_constants);
// Khởi tạo giao diện quản lý admin và các task
add_action('admin_menu', function() {
    new Scraper_Admin_Interface();
});
// Register wp_ajax for manual run a task
add_action('wp_ajax_'.TDX_CONST['plugin_prefix_name'].'_run_task', 'manual_run_task');
// Register wp_ajax for scrape single url
add_action('wp_ajax_'.TDX_CONST['plugin_prefix_name'].'_scrape_url', 'scrape_single_url');

function tdx_custom_schedule($schedules) {
    $schedules['every_minute'] = array(
            'interval' => 60,
            'display' => 'Every Minute'
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'tdx_custom_schedule' );

// Khởi tạo cron scheduler
register_activation_hook(__FILE__, ['Scraper_Scheduler', 'activate']);
register_deactivation_hook(__FILE__, ['Scraper_Scheduler', 'deactivate']);

// Đăng ký hàm kích hoạt plugin
register_activation_hook(__FILE__, 'scraper_plugin_activate');

// Hàm kích hoạt plugin
function scraper_plugin_activate() {
    global $wpdb;

    // Tên bảng (sử dụng prefix của WordPress)
    $table_name = $wpdb->prefix . 'scraper_tasks';

    // Câu lệnh SQL để tạo bảng
    $charset_collate = $wpdb->get_charset_collate();
    // $sql_alters = "ALTER TABLE $table_name ADD COLUMN max_post_per_run tinyInt(4) NOT NULL DEFAULT 1";
    // $wpdb->query($sql_alters);
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        author mediumint(9) NOT NULL default 1,
        category mediumint(9) NOT NULL default 1,
        task_name varchar(255) NOT NULL,
        url varchar(1000) NOT NULL,
        status varchar(50) NOT NULL default 'draft',
        schedule varchar(255) NOT NULL,
        max_post_per_run tinyInt(4) NOT NULL DEFAULT 1,
        rewrite_competitor_info tinyInt(2) NOT NULL DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    // Create scraper_task_logs table
    $table_name = $wpdb->prefix . Scraper_Log::$table_name;
    $sql .= "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        task_id mediumint(9) NOT NULL,
        post_id mediumint(9) NULL,
        running_status varchar(50) NOT NULL default 'success',
        scrape_url text NULL,
        running_log text NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Sử dụng dbDelta để tạo bảng
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
/**
 * Manual run a task
 * @return void
 */

function manual_run_task() {
    try {
        $task_id = (int) $_POST['task_id'];
        $task = Scraper_Task_Manager::get_task($task_id);
        if ($task) {
            Scraper_Scheduler::process_task($task->id, true);
        }
    }
    catch (Exception $e) {
        echo $e->getMessage();
    }
    echo 'done';
    wp_die();
}

/**
 * Scrape single url
 * @return void
 */
function scrape_single_url() {
    try {
        $url = $_POST['scrape_url'];
        $categories = $_POST['tdx_post_category'];
        $author = $_POST['tdx_post_author'];
        $status = $_POST['tdx_post_status'];
        $post_type = $_POST['tdx_post_type'];
        $is_rewrite_competitor_info = boolval($_POST['tdx_rewrite_competitor_info']);
        $competitor_info = $_POST['tdx_competitor_info'];
        $rewrite_responses = Tdx_Scrapper::scrape_and_rewrite_single_url($url, $is_rewrite_competitor_info, $competitor_info);
        // Define variables for task log
        $tl_task_id = 0;
        $tl_running_log = '';
        $tl_post_id = 0;
        $tl_scrape_url = $url;
        $tl_running_status = 'failed';
        if ($rewrite_responses && !isset($rewrite_responses['error'])) {
            $post_id = wp_insert_post([
                'post_title' => $rewrite_responses['title'],
                'post_content' => $rewrite_responses['content'],
                'post_status' => $status,
                'post_author' => $author,
                'post_category' => $categories,
                'post_type' => $post_type,
                'excerpt' => $rewrite_responses['excerpt'],
                'tags_input' => $rewrite_responses['tags'],
                'meta_input' => [
                    'rank_math_description' => $rewrite_responses['excerpt'] ?? '',
                    'rank_math_focus_keyword' => $rewrite_responses['focus_keyword'] ?? '',
                    '_wp_scrapergpt_post_source' => $url ?? '',
                ],
            ]);
            if (is_wp_error($post_id)) {
                $tl_running_log = 'Error: ' . $post_id->get_error_message();
                throw new Exception('Error when creating post: ' . $post_id->get_error_message());
            }
            $tl_post_id = $post_id;
            $tl_running_log = 'Đăng bài viết thành công: ' . $url;
            $tl_running_status = 'success';
            // Return post edit link in HTML
            echo 'Scrape thành công. <a href="' . get_edit_post_link($post_id) . '" target="_blank">Sửa bài viết</a>';
        } else {
            echo 'Lỗi: ' . $rewrite_responses['error'] ?? '';
            $tl_running_log = 'Error: ' . $rewrite_responses['error'] ?? '';
        }
    }
    catch (Exception $e) {
        echo $e->getMessage();
        $tl_running_log = 'Error: ' . $e->getMessage();
    }
    try {
        Scraper_Log::save_task_log($tl_task_id, $tl_post_id, $tl_running_status, $tl_running_log, $tl_scrape_url);
    }
    catch (Exception $e) {}
    wp_die();
}