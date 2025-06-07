<?php
// Kiểm tra quyền truy cập
if (!current_user_can('manage_options')) {
    wp_die(__('Bạn không có quyền truy cập vào trang này.'));
}

// Xử lý lưu cài đặt
if (isset($_POST['submit'])) {
    check_admin_referer('wp_post-generator_gpt_settings_save');

    // Lưu cài đặt n8n webhook
    update_option('wp_n8n_webhook_url', esc_url_raw($_POST['n8n_webhook_url']));
    update_option('wp_n8n_webhook_username', sanitize_text_field($_POST['n8n_webhook_username']));
    update_option('wp_n8n_webhook_password', sanitize_text_field($_POST['n8n_webhook_password']));

    echo '<div class="updated"><p>Cài đặt đã được lưu.</p></div>';
}

// Lấy giá trị hiện tại của các cài đặt
$webhook_url = get_option('wp_n8n_webhook_url', '');
$webhook_username = get_option('wp_n8n_webhook_username', '');
$webhook_password = get_option('wp_n8n_webhook_password', '');
?>

<div class="wrap">
    <h1>Cài đặt AI Post Generator</h1>

    <form method="post" action="">
        <?php wp_nonce_field('wp_post-generator_gpt_settings_save'); ?>

        <table class="form-table">
            <!-- Thêm cài đặt n8n webhook -->
            <tr>
                <th colspan="2">
                    <h2>Cài đặt n8n Webhook</h2>
                </th>
            </tr>
            <tr>
                <th scope="row">
                    <label for="n8n_webhook_url">Webhook URL</label>
                </th>
                <td>
                    <input type="url" id="n8n_webhook_url" name="n8n_webhook_url" value="<?php echo esc_url($webhook_url); ?>" class="regular-text" required>
                    <p class="description">Nhập URL webhook của n8n workflow của bạn.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="n8n_webhook_username">Username</label>
                </th>
                <td>
                    <input type="text" id="n8n_webhook_username" name="n8n_webhook_username" placeholder="Nhập username" class="regular-text">
                    <p class="description">Username cho Basic Authentication (để trống nếu không cần).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="n8n_webhook_password">Password</label>
                </th>
                <td>
                    <input type="password" id="n8n_webhook_password" name="n8n_webhook_password" placeholder="Nhập password" class="regular-text">
                    <p class="description">Password cho Basic Authentication (để trống nếu không cần).</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="Lưu cài đặt">
        </p>
    </form>
</div>