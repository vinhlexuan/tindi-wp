<?php
/**
 * Class Scraper_Logs
 * DB table: scraper_task_logs
 */

class Scraper_Log {
	public static $table_name = 'scraper_task_logs';
	public static function save_task_log($task_id, $post_id, $running_status, $running_log, $scrape_url = null) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;
		$wpdb->insert($table_name, [
			'task_id' => $task_id,
			'post_id' => $post_id,
			'running_status' => $running_status,
			'running_log' => $running_log,
			'scrape_url' => $scrape_url,
			'created_at' => current_time('mysql'),
		]);
	}
	/**
	 * Get all task logs paginated
	 */
	public static function get_all_task_logs($task_id = null, $page = 1, $per_page = 10) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;
		$offset = ((int)$page - 1) * $per_page;
		$where = $task_id ? "WHERE task_id = $task_id" : '';
		return $wpdb->get_results("SELECT $table_name.*, task_name, schedule, url FROM $table_name
			LEFT JOIN {$wpdb->prefix}scraper_tasks ON $table_name.task_id = {$wpdb->prefix}scraper_tasks.id
			$where
			ORDER BY id DESC
			LIMIT $per_page OFFSET $offset");
	}

	/**
	 * Get total logs count
	 */
	public static function get_total_logs_count($task_id = null) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;
		$where = $task_id ? "WHERE task_id = $task_id" : '';
		return $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
	}
	/**
	 * Check if provided scrape URL is already processed
	 */
	public static function is_url_already_processed($scrape_url) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$table_name;
		$scrape_url = esc_url($scrape_url);
		$existing_log = $wpdb->get_row("SELECT id FROM $table_name WHERE scrape_url LIKE '|%$scrape_url|%' AND running_status = 'success'");
		return $existing_log ? true : false;
	}
}