<?php
/**
 * Plugin Name: AI Gen Post
 * Description: Plugin tự động đăng bài viết dựa vào tiêu đề có dùng AI và n8n workflow.
 * Version: 1.0
 * Author: vinh.lexuan0112@gmail.com
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Load các file class cần thiết
require_once plugin_dir_path(__FILE__) . 'includes/class-api-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-callback-handler.php';

define('TDX_CONST', require_once plugin_dir_path(__FILE__) . 'includes/constants.php');

// Khởi tạo giao diện quản lý admin
add_action('admin_menu', function() {
    new Admin_Interface();
});

// Đăng ký hàm kích hoạt plugin
register_activation_hook(__FILE__, 'post_generator_plugin_activate');

// Hàm kích hoạt plugin
function post_generator_plugin_activate() {
    // Set default options
    update_option('wp_n8n_webhook_url', '');
    update_option('wp_n8n_webhook_username', '');
    update_option('wp_n8n_webhook_password', '');
}

// Đăng ký handler cho n8n webhook
add_action('wp_ajax_'.TDX_CONST['plugin_prefix_name'].'_trigger_webhook', 'direct_trigger_n8n_webhook');

/**
 * Trigger n8n webhook directly using API_Handler class
 */
function direct_trigger_n8n_webhook() {
    check_ajax_referer(TDX_CONST['plugin_prefix_name'].'_trigger_webhook');
    
    try {
        // Get data from AJAX request
        $post_title = isset($_POST['post_title']) ? sanitize_text_field($_POST['post_title']) : '';
        $post_data = isset($_POST['post_data']) ? $_POST['post_data'] : [];
        
        if (empty($post_title)) {
            echo 'Lỗi: Vui lòng nhập tiêu đề bài viết';
            wp_die();
        }
        
        // Gọi trực tiếp API_Handler::trigger_n8n_webhook
        $response = API_Handler::trigger_n8n_webhook($post_title, $post_data);
        
        // Trả về kết quả
        if (isset($response['error'])) {
            echo 'Lỗi: ' . $response['error'];
        } elseif (isset($response['post_id']) && $response['post_id'] > 0) {
            $post_id = $response['post_id'];
            echo 'Bài viết đã được tạo thành công. <a href="' . get_edit_post_link($post_id) . '" target="_blank">Sửa bài viết</a>';
        } else {
            echo 'Yêu cầu đã được gửi thành công. Bài viết đang được xử lý...';
        }
    }
    catch (Exception $e) {
        echo 'Lỗi: ' . $e->getMessage();
    }
    
    wp_die();
}