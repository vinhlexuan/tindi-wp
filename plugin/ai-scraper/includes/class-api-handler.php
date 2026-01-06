<?php

class Scraper_API_Handler {

    /**
     * Gọi API để lấy danh sách URL từ trang.
     */
    public static function get_urls_from_html($url, $html_content) {
        $response = wp_remote_post(TDX_CONST['api_url_extractor'], [
            'body' => json_encode([
                'url' => $url,
                'html' => $html_content
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-key' => get_option('wp_scraper_gpt_api_key', '')
            ],
            'timeout' => 120,
            'sslverify'   => false,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Gọi API để viết lại nội dung bài viết.
     */
    public static function rewrite_content($html_content, $rewrite_competitor_info, $user_info, $url) {
        try {
            $username = get_option('wp_scraper_api_username', '');
            $password = get_option('wp_scraper_api_password', '');
            
            // Kiểm tra xem có username và password không
            if (empty($username) || empty($password)) {
                return ['error' => 'Username and password are required. Please configure them in Settings.'];
            }
            
            // Lấy webhook URL từ settings
            $webhook_url = get_option('wp_scraper_api_webhook_url', '');
            if (empty($webhook_url)) {
                return ['error' => 'Webhook URL is required. Please configure it in Settings.'];
            }
            
            // Chuẩn bị headers
            $headers = ['Content-Type' => 'application/json'];
            
            // Thêm Basic Auth nếu có username và password
            if (!empty($username) && !empty($password)) {
                $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
            }
            
            $response = wp_remote_post($webhook_url, [
                'body' => json_encode([
                    'html_content' => $html_content,
                    'rewrite_competitor_info' => (bool)$rewrite_competitor_info,
                    'user_website_info' => $user_info,
                    'url' => $url,
                ]),
                'headers' => $headers,
                'timeout' => 600,
                'sslverify'   => false,
            ]);
            
            if (is_wp_error($response)) {
                $error_info = 'API request failed: ' . $response->get_error_message();
                $error_info .= ' | Error Code: ' . $response->get_error_code();
                $error_info .= ' | Error Data: ' . print_r($response->get_error_data(), true);
                $error_info .= ' | Webhook URL: ' . $webhook_url;
                return ['error' => $error_info];
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $body = wp_remote_retrieve_body($response);
                return ['error' => 'API returned error code ' . $response_code . ': ' . $body];
            }

            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => 'Invalid JSON response from API: ' . json_last_error_msg()];
            }
            
            return $decoded;
        }
        catch (Exception $e) {
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Gọi API để lấy số credit còn lại.
     */
    public static function get_remaining_credit($key) {
        $api_key = get_option('wp_scraper_gpt_api_key', '');
        $response = wp_remote_post(TDX_CONST['api_remain_credits'], [
            'body' => json_encode(['key' => $key]),
            'headers' => [
                'Content-Type' => 'application/json', 
                'x-key' => $api_key
            ],
            'timeout' => 30,
            'sslverify'   => false,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}

