<?php

class Scraper_Admin_Interface {

    public function __construct() {
        // Tạo menu khi admin truy cập
        add_action('admin_menu', [$this, 'create_menu']);
        // Đăng ký scripts và styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        // Đăng ký AJAX endpoints
        add_action('wp_ajax_api_remain_credits', [$this, 'handle_remain_credits']);
        add_action('wp_ajax_api_topup', [$this, 'handle_topup']);
    }

    // Hàm đăng ký và enqueue scripts
    public function enqueue_admin_scripts($hook) {
        // Kiểm tra xem có phải đang ở trang của plugin không
        if (strpos($hook, 'scraper-plugin') !== false) {
            // Đăng ký và enqueue jQuery (mặc dù WordPress đã có sẵn)
            wp_enqueue_script('jquery');
            
            // Đăng ký và enqueue Dashicons
            wp_enqueue_style('dashicons');
            
            // Đăng ký và enqueue script của plugin
            wp_enqueue_script(
                'ai-scraper-admin',
                plugins_url('../assets/js/admin.js', __FILE__),
                array('jquery'),
                '1.0.0',
                true
            );

            // Thêm các biến JavaScript nếu cần
            wp_localize_script(
                'ai-scraper-admin',
                'aiScraperAdmin',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ai_scraper_nonce')
                )
            );
        }
    }

    // Hàm tạo menu và submenu
    public function create_menu() {
        // Thêm menu chính
        add_menu_page(
            'AI Scraper',          // Tiêu đề trang
            'AI Scraper',           // Tên menu
            'manage_options',          // Quyền truy cập (chỉ quản trị viên)
            'scraper-plugin',          // Slug của trang
            [$this, 'display_task_list'], // Hàm callback để hiển thị nội dung
            'dashicons-admin-site-alt3' // Icon của menu
        );

        // Thêm submenu cho trang cài đặt
        add_submenu_page(
            'scraper-plugin',           // Parent slug (liên kết với menu chính)
            'Settings',                 // Tiêu đề trang
            'Settings',                 // Tên submenu
            'manage_options',           // Quyền truy cập (chỉ quản trị viên)
            'scraper-plugin-settings',  // Slug của submenu
            [$this, 'display_settings_page'] // Hàm callback để hiển thị nội dung
        );
        // Add submenu page for task logs
        add_submenu_page(
            'scraper-plugin',           // Parent slug (liên kết với menu chính)
            'Task Logs',                // Tiêu đề trang
            'Task Logs',                // Tên submenu
            'manage_options',           // Quyền truy cập (chỉ quản trị viên)
            'scraper-plugin-logs',      // Slug của submenu
            [$this, 'display_logs_page'] // Hàm callback để hiển thị nội dung
        );
    }

    // Hàm hiển thị danh sách task
    public function display_task_list() {
        include plugin_dir_path(__FILE__) . '../views/task-list-page.php';
    }

    // Hàm hiển thị trang cài đặt
    public function display_settings_page() {
        include plugin_dir_path(__FILE__) . '../views/admin-settings-page.php';
    }

    // Hàm hiển thị trang logs
    public function display_logs_page() {
        include plugin_dir_path(__FILE__) . '../views/logs-list-page.php';
    }

    // Hàm xử lý AJAX request để lấy số dư
    public function handle_remain_credits() {
        check_ajax_referer('ai_scraper_nonce');
        
        try {
            $api_url = TDX_CONST['api_remain_credits'];
            $api_key = get_option('wp_scraper_gpt_api_key', '');
            
            // Gọi API để lấy số dư với header x-key
            $response = wp_remote_get($api_url, [
                'headers' => [
                    'x-key' => $api_key
                ]
            ]);
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data) {
                throw new Exception('Invalid response format');
            }

            // Thêm tên plugin vào response
            $data['plugin_name'] = TDX_CONST['plugin_name'];
            
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // Hàm xử lý AJAX request để lấy nội dung form nạp tiền
    public function handle_topup() {
        check_ajax_referer('ai_scraper_nonce');
        
        try {
            $api_url = TDX_CONST['api_topup'];
            $api_key = get_option('wp_scraper_gpt_api_key', '');
            
            // Gọi API để lấy nội dung form nạp tiền
            $response = wp_remote_get($api_url, [
                'headers' => [
                    'x-key' => $api_key
                ]
            ]);
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data || !isset($data['topup_content'])) {
                throw new Exception('Invalid response format');
            }
            
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}

// Khởi tạo giao diện admin khi plugin được load
new Scraper_Admin_Interface();

