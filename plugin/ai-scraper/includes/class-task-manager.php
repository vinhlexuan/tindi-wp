<?php

class Scraper_Task_Manager {

    /**
     * Lưu task mới vào database.
     */
    public static function create_task($task_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scraper_tasks';
        $wpdb->insert($table_name, $task_data);
    }

    /**
     * Lấy danh sách các task.
     */
    public static function get_tasks() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scraper_tasks';
        return $wpdb->get_results("SELECT * FROM $table_name");
    }

    /**
     * Xóa task.
     */
    public static function delete_task($task_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scraper_tasks';
        $wpdb->delete($table_name, ['id' => $task_id]);
    }

    /**
     * Lấy thông tin của một task.
     */
    public static function get_task($task_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scraper_tasks';
        return $wpdb->get_row("SELECT * FROM $table_name WHERE id = $task_id");
    }

    /**
     * Cập nhật thông tin của một task.
     */
    public static function update_task($task_id, $task_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scraper_tasks';
        return $wpdb->update($table_name, $task_data, ['id' => $task_id]);
    }
}

