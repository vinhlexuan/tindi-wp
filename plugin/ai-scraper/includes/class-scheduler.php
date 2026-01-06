<?php

class Scraper_Scheduler {
    /**
     * Kích hoạt cron job khi plugin được kích hoạt.
     */
    public static function activate() {
        if (!wp_next_scheduled('scraper_run_tasks')) {
            wp_schedule_event(time(), 'every_minute', 'scraper_run_tasks');
        }
    }

    /**
     * Hủy cron job khi plugin bị vô hiệu hóa.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('scraper_run_tasks');
    }

    /**
     * Chạy các task.
     */
    public static function run_tasks() {
        $tasks = Scraper_Task_Manager::get_tasks();
        $current_minute = date('i');
        $current_hour = date('H');
        foreach ($tasks as $task) {
            /**
             * Check if task is on schedule by comapring current time with task's schedule time
             */
            if ($task->schedule == 'manual') {
                continue;
            }
            // file_put_contents('./log.txt', 'Current time: ' . $current_hour . ':' . $current_minute . PHP_EOL, FILE_APPEND);
            // Every 30 minutes tasks, run at 0 and 30 minutes of each hour
            if ($task->schedule == 'every_30_minutes') {
                if ($current_minute == '00' || $current_minute == '30') {
                    // file_put_contents('./log.txt', 'every_30_minutes starting...' . PHP_EOL, FILE_APPEND);
                    self::process_task($task->id);
                }
            }
            // Every hour tasks, run at 15 minute of each hour
            if ($task->schedule == 'hourly') {
                if ($current_minute == '15') {
                    // file_put_contents('./log.txt', 'hourly starting...' . PHP_EOL, FILE_APPEND);
                    self::process_task($task->id);
                }
            }
            // Twice a day tasks, run at 45 minute of 0, 12 hours
            if ($task->schedule == 'twice_a_day') {
                if ($current_minute == '45' && ($current_hour == '00' || $current_hour == '12')) {
                    self::process_task($task->id);
                }
            }
            // Daily tasks, run at 20 minute of 0 hour
            if ($task->schedule == 'daily') {
                if ($current_minute == '20' && $current_hour == '00') {
                    self::process_task($task->id);
                }
            }
            // Weekly tasks, run at 10 minute of 4 hour of Monday
            if ($task->schedule == 'weekly') {
                if ($current_minute == '10' && $current_hour == '04' && date('N') == 1) {
                    self::process_task($task->id);
                }
            }
        }
    }

    /**
     * Process a task.
     */
    public static function process_task($task_id, $is_manual = false) {
        $task = Scraper_Task_Manager::get_task($task_id);
        $post_id = null;
        $running_status = 'failed';
        $running_log = '';
        $scrape_urls = '';
        try {
            $html_content = Tdx_Scrapper::scrape_html_content($task->url);
            $html_content = Tdx_Scrapper::remove_unnecessary_html_data($html_content);
            // Send request to API server to extract URLs
            $api_response = Scraper_API_Handler::get_urls_from_html($task->url, $html_content);
            if (gettype($api_response) !== 'array' || !array_key_exists('urls', $api_response)) {
                throw new Exception('API response is not valid. ' . json_encode($api_response));
            }
            if (count($api_response['urls']) == 0) {
                throw new Exception('No URLs found in the content');
            }
            // Loop through each URL and get the content (max loop count small or equal task's max_post_per_run), then rewrite it.
            $loop_count = 0;
            foreach ($api_response['urls'] as $url) {
                try {
                    // Check if URL is already processed
                    if (Scraper_Log::is_url_already_processed($url)) {
                        continue;
                    }
                    if ($loop_count >= $task->max_post_per_run) {
                        break;
                    }
                    $loop_count++;
                    $post_content = Tdx_Scrapper::scrape_html_content($url);
                    $post_content = Tdx_Scrapper::remove_unnecessary_html_data($post_content);
                    // Send request to API server to rewrite content
                    $rewrite_response = Scraper_API_Handler::rewrite_content($post_content, $task->rewrite_competitor_info, get_option('wp_scraper_gpt_user_info', ''), $url);
                    if (gettype($rewrite_response) !== 'array' || !array_key_exists('content', $rewrite_response)) {
                        throw new Exception('API response is not valid. ' . json_encode($rewrite_response));
                    }
                    // Convert $task->category to array
                    $task->category = explode(',', $task->category);
                    $post_meta_description = $rewrite_response['excerpt'] ?? '';
                    $post_meta_focus_keyword = $rewrite_response['focus_keyword'] ?? '';
                    $post_meta_source = $url;
                    // Create post
                    $post_id = wp_insert_post([
                        'post_title' => $rewrite_response['title'],
                        'post_content' => $rewrite_response['content'],
                        'post_status' => $task->post_status,
                        'post_author' => $task->author,
                        'post_category' => $task->category,
                        'excerpt' => $rewrite_response['excerpt'],
                        'tags_input' => $rewrite_response['tags'],
                        'meta_input' => [
                            '_yoast_wpseo_metadesc' => $post_meta_description,
                            '_yoast_wpseo_focuskw' => $post_meta_focus_keyword,
                            '_wp_scrapergpt_post_source' => $post_meta_source,
                        ],
                    ]);
                    if (is_wp_error($post_id)) {
                        throw new Exception('Error when creating post: ' . $post_id->get_error_message());
                    }
                    $running_status = 'success';
                    $running_log = 'Post created successfully';
                    $scrape_urls .= '|' . $url . '|';
                    // file_put_contents('./log.txt', $running_log, FILE_APPEND);

                }
                catch (Exception $e) {
                    // Log lỗi
                    error_log('Scraper_Scheduler@process_task error: ' . $e->getMessage());
                    $running_status = 'failed';
                    $running_log = $e->getMessage();
                }
            }
        }
        catch (Exception $e) {
            // Log lỗi
            error_log('Scraper_Scheduler@process_task error: ' . $e->getMessage());
            $running_status = 'failed';
            $running_log = $e->getMessage();
        }
        // Save to logs table
        Scraper_Log::save_task_log(
            $task_id,
            $post_id,
            $running_status,
            $running_log . ' [' . ($is_manual ? 'Manual' : 'Scheduled') . ']',
            $scrape_urls
        );
        return true;
    }
}

// Hook để chạy các task theo lịch trình
add_action('scraper_run_tasks', ['Scraper_Scheduler', 'run_tasks']);

