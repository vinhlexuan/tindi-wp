<?php
// Kiểm tra quyền truy cập
if (!current_user_can('manage_options')) {
    wp_die(__('Bạn không có quyền truy cập vào trang này.'));
}

// Xử lý lưu cài đặt
if (isset($_POST['submit'])) {
    check_admin_referer('wp_scraper_gpt_settings_save');

    // Lưu các giá trị cài đặt
    update_option('wp_scraper_api_username', sanitize_text_field($_POST['api_username']));
    update_option('wp_scraper_api_password', sanitize_text_field($_POST['api_password']));
    update_option('wp_scraper_api_webhook_url', esc_url_raw($_POST['api_webhook_url']));
    update_option('wp_scraper_gpt_user_info', sanitize_textarea_field($_POST['user_info']));

    echo '<div class="updated"><p>Cài đặt đã được lưu.</p></div>';
}

// Lấy giá trị hiện tại của các cài đặt
$api_username = get_option('wp_scraper_api_username', '');
$api_webhook_url = get_option('wp_scraper_api_webhook_url', '');
$user_info = get_option('wp_scraper_gpt_user_info', '');
?>

<div class="wrap">
    <h1>Cài đặt Scraper Plugin</h1>

    <form method="post" action="">
        <?php wp_nonce_field('wp_scraper_gpt_settings_save'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api_username">Username</label>
                </th>
                <td>
                    <input type="text" id="api_username" name="api_username" value="<?php echo esc_attr($api_username); ?>" class="regular-text">
                    <p class="description">Nhập username của bạn để xác thực API. Liên hệ Zalo: <a href="https://zalo.me/0916019986" target="_blank">0916019986</a> để được hỗ trợ.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="api_password">Password</label>
                </th>
                <td>
                    <input type="password" id="api_password" name="api_password" value="" class="regular-text">
                    <p class="description">Nhập password của bạn để xác thực API.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="api_webhook_url">Webhook URL</label>
                </th>
                <td>
                    <input type="url" id="api_webhook_url" name="api_webhook_url" value="<?php echo esc_attr($api_webhook_url); ?>" class="large-text" placeholder="https://wp.socibi.com/webhook/wp-rewrite-content">
                    <p class="description">Nhập URL webhook API để viết lại nội dung bài viết.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="user_info">Thông tin website của bạn</label>
                </th>
                <td>
                    <textarea id="user_info" name="user_info" class="large-text" rows="5" placeholder="Nhập thông tin website của bạn. VD:
Công ty TNHH ABC
Địa chỉ: 123 Nguyễn Văn Cừ, Quận 5, TP.HCM
Điện thoại: 0909090909
Email: contact@abc.com
Website: https://abc.com"><?php echo esc_textarea($user_info); ?></textarea>
                    <p class="description">Nhập thông tin website của bạn để thay thế thông tin đối thủ khi viết lại nội dung.</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="Lưu cài đặt">
        </p>
    </form>
</div>

