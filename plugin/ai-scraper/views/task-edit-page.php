<?php
// Kiểm tra quyền truy cập
if (!current_user_can('manage_options')) {
    wp_die(__('Bạn không có quyền truy cập vào trang này.'));
}

// Xử lý khi người dùng submit form
if (isset($_POST['submit'])) {
    check_admin_referer('scraper_plugin_task_save');

    // Lưu task vào database
    $task_data = [
        'task_name' => sanitize_text_field($_POST['task_name']),
        'url' => sanitize_text_field($_POST['url']),
        'category' => sanitize_text_field($_POST['category']),
        'author' => sanitize_text_field($_POST['author']),
        'status' => sanitize_text_field($_POST['status']),
        'schedule' => sanitize_text_field($_POST['schedule']),
        'rewrite_competitor_info' => isset($_POST['rewrite_competitor_info']) ? 1 : 0,
    ];

    if (isset($_GET['id'])) {
        Scraper_Task_Manager::update_task($_GET['id'], $task_data);
    } else {
        Scraper_Task_Manager::create_task($task_data);
    }

    echo '<div class="updated"><p>Task đã được lưu.</p></div>';
    // Redirect về trang danh sách task
    // wp_redirect(admin_url('admin.php?page=scraper-plugin'));
}

// Nếu đang chỉnh sửa, lấy thông tin task hiện tại
$task = null;
if (isset($_GET['id'])) {
    $task = Scraper_Task_Manager::get_task($_GET['id']);
}

// Các giá trị mặc định nếu tạo mới
$task_name = $task ? $task->task_name : '';
$url = $task ? $task->url : '';
$category = $task ? $task->category : '';
$author = $task ? $task->author : '';
$status = $task ? $task->status : 'draft';
$schedule = $task ? $task->schedule : 'hourly';
$rewrite_competitor_info = $task ? $task->rewrite_competitor_info : 1;
$max_post_per_run = $task ? $task->max_post_per_run : 1;
?>

<div class="wrap">
    <h1><?php echo isset($_GET['id']) ? 'Chỉnh sửa Task' : 'Thêm Task Mới'; ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('scraper_plugin_task_save'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="url">Tên task</label>
                </th>
                <td>
                    <input type="text" id="task_name" name="task_name" value="<?php echo esc_attr($task_name); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="url">URL của trang chứa danh sách bài viết</label>
                </th>
                <td>
                    <input type="text" id="url" name="url" value="<?php echo esc_attr($url); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="category">Danh mục</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_categories([
                        'name' => 'category',
                        'selected' => $category,
                    ]);
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="author">Tác giả</label>
                </th>
                <td>
                    <?php
                    wp_dropdown_users([
                        'name' => 'author',
                        'selected' => $author,
                    ]);
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="status">Trạng thái bài viết</label>
                </th>
                <td>
                    <select id="status" name="status">
                        <option value="publish" <?php selected($status, 'publish'); ?>>Publish</option>
                        <option value="draft" <?php selected($status, 'draft'); ?>>Draft</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="schedule">Lịch trình chạy</label>
                </th>
                <td>
                    <select id="schedule" name="schedule">
                        <?php
                        foreach (TDX_CONST['schedule_interval'] as $key => $value) {
                            echo '<option value="' . $key . '" ' . selected($schedule, $key) . '>' . $value . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="max_post_per_run">Max post per run</label>
                </th>
                <td>
                    <input type="number" id="max_post_per_run" name="max_post_per_run" value="<?php echo esc_attr($max_post_per_run); ?>" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rewrite_competitor_info">Rewrite competitor info?</label>
                </th>
                <td>
                    <input type="checkbox" id="rewrite_competitor_info" name="rewrite_competitor_info" value="1" <?php checked($rewrite_competitor_info, 1); ?>>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="Lưu Task">
        </p>
    </form>
</div>

