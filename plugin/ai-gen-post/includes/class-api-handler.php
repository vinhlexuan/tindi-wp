<?php

class API_Handler {

    /**
     * Gọi webhook n8n để tạo bài viết từ tiêu đề.
     */
    public static function trigger_n8n_webhook($post_title, $post_data) {
        try {
            // Lấy thông tin webhook từ cài đặt
            $webhook_url = get_option('wp_n8n_webhook_url', '');
            $username = get_option('wp_n8n_webhook_username', '');
            $password = get_option('wp_n8n_webhook_password', '');
            
            // Kiểm tra xem có URL webhook không
            if (empty($webhook_url)) {
                return ['error' => 'Webhook URL chưa được cấu hình'];
            }
            
            // Chuẩn bị headers
            $headers = ['Content-Type' => 'application/json'];
            
            // Thêm Basic Auth nếu có username và password
            if (!empty($username) && !empty($password)) {
                $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
            }
            
            // Chuẩn bị dữ liệu gửi đi
            $payload = [
                'post_title' => $post_title,
                'post_settings' => [
                    'categories' => isset($post_data['categories']) ? $post_data['categories'] : [],
                    'author' => isset($post_data['author']) ? $post_data['author'] : 1,
                    'status' => isset($post_data['status']) ? $post_data['status'] : 'draft',
                    'post_type' => isset($post_data['post_type']) ? $post_data['post_type'] : 'posts',
					'gen_image' => isset($post_data['gen_image']) ? $post_data['gen_image'] : false,
                ],
            ];
            
            // Gọi webhook
            $response = wp_remote_post($webhook_url, [
                'body' => json_encode($payload),
                'headers' => $headers,
                'timeout' => 120,
                'sslverify' => false,
            ]);
            
            if (is_wp_error($response)) {
                return ['error' => 'Lỗi kết nối đến webhook: ' . $response->get_error_message()];
            }
            
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            // Kiểm tra kết quả trả về từ webhook
            if (is_array($result)) {
                return $result;
            } else {
                // Nếu webhook không trả về JSON hợp lệ, tạo response riêng
                return [
                    'success' => true,
                    'message' => 'Đã gửi yêu cầu thành công',
                    'raw_response' => $body
                ];
            }
        }
        catch (Exception $e) {
            return ['error' => 'Lỗi xử lý: ' . $e->getMessage()];
        }
    }
}