<?php
// Kiểm tra quyền truy cập
if (!current_user_can('manage_options')) {
    wp_die(__('Bạn không có quyền truy cập vào trang này.'));
}
// If action is `add`
if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')) {
    include_once 'task-edit-page.php';
    return;
}
// Delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    Scraper_Task_Manager::delete_task($_GET['id']);
    echo '<div class="updated"><p>Task đã được xóa.</p></div>';
}

// Lấy danh sách các task từ database
$tasks = Scraper_Task_Manager::get_tasks();

?>

<div class="wrap">
    <div id="scraper-task-list" style="display: none;">
        <h1 class="wp-heading-inline">Danh sách Scrape Tasks</h1>
        <a href="?page=scraper-plugin&action=add" class="page-title-action">Thêm Task Mới</a>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>URL</th>
                    <th>Danh mục</th>
                    <th>Tác giả</th>
                    <th>Số bài mỗi lần</th>
                    <th>Trạng thái</th>
                    <th>Lịch trình</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tasks)): ?>
                    <?php foreach ($tasks as $task): ?>
                        <?php
                        // Lấy thông tin category
                        $category = get_category($task->category);
                        // Lấy thông tin author
                        $author = get_user_by('id', $task->author);
                        ?>
                        <tr>
                            <!-- <td><?php echo esc_html($task->id); ?></td> -->
                            <td><?php echo esc_html($task->url); ?></td>
                            <td><?php echo esc_html($category->name ?? '--'); ?></td>
                            <td><?php echo esc_html($author->user_login ?? '--'); ?></td>
                            <td><?php echo esc_html($task->max_post_per_run); ?></td>
                            <td><?php echo esc_html($task->status); ?></td>
                            <td><?php echo esc_html(TDX_CONST['schedule_interval'][$task->schedule] ?? $task->schedule); ?></td>
                            <td>
                                <!-- Run now button -->
                                <a onclick="runTask('<?php echo esc_attr($task->id); ?>', this)" title="Run this task now" class="button">▶</a>
                                <a href="?page=scraper-plugin&action=edit&id=<?php echo esc_attr($task->id); ?>" class="button">Sửa</a>
                                <a href="?page=scraper-plugin&action=delete&id=<?php echo esc_attr($task->id); ?>" class="button" onclick="return confirm('Bạn có chắc chắn muốn xóa task này?');">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Chưa có task nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <hr class="tdx-separator" />
    </div>
    <!-- Form nhập đường dẫn để scrape -->
    <div id="scrape-form" class="postbox">
    <form method="post" action="" onsubmit="return scrapeUrl();" class="tdx-scrape-form inside">
        <h1 class="wp-heading-inline">Scrape bài theo URL</h1>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="scrape_url">URL</label></th>
                <td><input type="text" name="scrape_url" id="scrape_url" class="large-text" placeholder="Nhập URL cần scrape"></td>
            </tr>

            <tr>
                <th scope="row"><label for="tdx_post_category">Chọn danh mục</label></th>
                <td>
                    <select name="tdx_post_category[]" id="tdx_post_category" multiple style="height: 120px; width: 100%;">
                        <?php
                        $categories = get_categories(array('hide_empty' => false));
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description">Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều danh mục.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tdx_post_author">Chọn tác giả</label></th>
                <td>
                    <select name="tdx_post_author" id="tdx_post_author" class="regular-text">
                        <?php
                        $users = get_users();
                        foreach ($users as $user) {
                            echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->user_login) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tdx_post_status">Chọn trạng thái</label></th>
                <td>
                    <select name="tdx_post_status" id="tdx_post_status" class="regular-text">
                        <option value="draft">Draft</option>
                        <option value="publish">Publish</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tdx_post_type">Chọn loại bài</label></th>
                <td>
                    <select name="tdx_post_type" id="tdx_post_type" class="regular-text">
                        <?php
                        $post_types = get_post_types(array('public' => true), 'objects');
                        foreach ($post_types as $post_type) {
                            echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="tdx_rewrite_competitor_info">Tự động thay thế thông tin liên hệ</label></th>
                <td>
                    <p><input type="checkbox" name="tdx_rewrite_competitor_info" id="tdx_rewrite_competitor_info" class="regular-text" value="1" checked onclick="toggleCompetitorInfo()"></p>
                    <p><textarea rows="5" name="tdx_competitor_info" id="tdx_competitor_info" class="large-text" placeholder="Nhập thông tin website của bạn. VD:
Công ty TNHH ABC
Địa chỉ: 123 Nguyễn Văn Cừ, Quận 5, TP.HCM
Điện thoại: 0909090909
Email: contact@abc.com
Website: https://abc.com"><?php echo esc_html(get_option('wp_scraper_gpt_user_info')); ?></textarea></p>
                    <p class="description">Tự động thay thế thông tin liên hệ trong nội dung bài viết (nếu có) bằng thông tin từ trang website của bạn.</p>
                </td>
            </tr>
            <tfoot>
                <tr>
                    <td></td>
                    <td>
                        <input type="submit" class="button button-primary" value="Scrape và viết lại bài viết">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <div class="tdx-scrape-result"></div>
                    </td>
                </tr>
            </tfoot>
        </table>

    </form>

    </div>
</div>
<script language="JavaScript">
    function runTask(taskId, _btn) {
        if (confirm('Bạn có chắc chắn muốn chạy task này?')) {
            // Change text of _btn to loading
            _btn.innerText = '⏳';
            // Disable button
            _btn.disabled = true;
            // Send AJAX request to run task
            jQuery.post(ajaxurl, {
                action: '<?php echo TDX_CONST['plugin_prefix_name'].'_run_task'; ?>',
                task_id: taskId,
                _ajax_nonce: '<?php echo wp_create_nonce(TDX_CONST['plugin_prefix_name'].'_run_task'); ?>'
            }, function(response) {
                // Change text of _btn back to normal
                _btn.innerText = '▶';
                // Enable button
                _btn.disabled = false;
                if (response == 'done') {
                    alert('Task đã chạy xong. Vui lòng kiểm tra log để xem chi tiết.');
                }
                else {
                    alert("Có lỗi xảy ra khi chạy task: " + response);
                }
                // location.reload();
            });
        }
    }

    function toggleCompetitorInfo() {
        var competitorInfo = jQuery('#tdx_rewrite_competitor_info').is(':checked');
        if (competitorInfo) {
            jQuery('#tdx_competitor_info').removeAttr('disabled');
        }
        else {
            jQuery('#tdx_competitor_info').attr('disabled', 'disabled');
        }
    }
    
    function scrapeUrl() {
        var scrapeUrl = jQuery('#scrape_url').val();
        if (scrapeUrl) {
            jQuery('.tdx-scrape-result').html('<p>Đang scrape, quá trình này có thể mất từ 1-3 phút...</p>');
            // Disable submit button
            jQuery('input[type="submit"]').prop('disabled', true);
            // Send AJAX request to scrape URL
            jQuery.post(ajaxurl, {
                action: '<?php echo TDX_CONST['plugin_prefix_name'].'_scrape_url'; ?>',
                scrape_url: scrapeUrl,
                _ajax_nonce: '<?php echo wp_create_nonce(TDX_CONST['plugin_prefix_name'].'_scrape_url'); ?>',
                tdx_post_category: jQuery('#tdx_post_category').val(),
                tdx_post_author: jQuery('#tdx_post_author').val(),
                tdx_post_status: jQuery('#tdx_post_status').val(),
                tdx_post_type: jQuery('#tdx_post_type').val(),
                tdx_rewrite_competitor_info: jQuery('#tdx_rewrite_competitor_info').is(':checked'),
                tdx_competitor_info: jQuery('#tdx_competitor_info').val(),
            }, function(response) {
                jQuery('.tdx-scrape-result').html(response);
                // Enable submit button
                jQuery('input[type="submit"]').prop('disabled', false);
            });
        }
        return false;
    }
</script>
