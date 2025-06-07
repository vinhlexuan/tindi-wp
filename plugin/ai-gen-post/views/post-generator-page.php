<?php
// Kiểm tra quyền truy cập
if (!current_user_can('manage_options')) {
    wp_die(__('Bạn không có quyền truy cập vào trang này.'));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">AI Post Generator</h1>
    <hr class="wp-header-end">
    
    <!-- Form nhập tiêu đề bài viết -->
    <div id="scrape-form" class="postbox">
        <form method="post" action="" onsubmit="return genContent();" class="tdx-scrape-form inside">
            <h2>Tạo bài viết mới</h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="post_title">Tiêu đề bài viết</label></th>
                    <td><input type="text" name="post_title" id="post_title" class="large-text" placeholder="Nhập tiêu đề bài viết"></td>
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

                <!-- Thêm trường gen ảnh -->
                <tr>
                    <th scope="row"><label for="tdx_gen_image">Tạo ảnh cho bài viết</label></th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="tdx_gen_image" name="tdx_gen_image">
                            <span class="slider round"></span>
                        </label>
                        <span style="margin-left: 10px;" id="gen_image_status">OFF</span>
                        <p class="description">Tự động tạo ảnh cho bài viết</p>

                        <style>
                            /* CSS cho toggle switch */
                            .switch {
                                position: relative;
                                display: inline-block;
                                width: 60px;
                                height: 34px;
                            }
                            .switch input {
                                opacity: 0;
                                width: 0;
                                height: 0;
                            }
                            .slider {
                                position: absolute;
                                cursor: pointer;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                background-color: #ccc;
                                transition: .4s;
                            }
                            .slider:before {
                                position: absolute;
                                content: "";
                                height: 26px;
                                width: 26px;
                                left: 4px;
                                bottom: 4px;
                                background-color: white;
                                transition: .4s;
                            }
                            input:checked + .slider {
                                background-color: #2196F3;
                            }
                            input:focus + .slider {
                                box-shadow: 0 0 1px #2196F3;
                            }
                            input:checked + .slider:before {
                                transform: translateX(26px);
                            }
                            .slider.round {
                                border-radius: 34px;
                            }
                            .slider.round:before {
                                border-radius: 50%;
                            }
                        </style>
                    </td>
                </tr>
                <tfoot>
                    <tr>
                        <td></td>
                        <td>
                            <input type="submit" class="button button-primary" value="Tạo bài viết">
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
    jQuery(document).ready(function($) {
        // Cập nhật text khi toggle switch thay đổi
        $('#tdx_gen_image').change(function() {
            if($(this).is(':checked')) {
                $('#gen_image_status').text('ON');
            } else {
                $('#gen_image_status').text('OFF');
            }
        });
    });
    
    function toggleCompetitorInfo() {
        var competitorInfo = jQuery('#tdx_rewrite_competitor_info').is(':checked');
        if (competitorInfo) {
            jQuery('#tdx_competitor_info').removeAttr('disabled');
        }
        else {
            jQuery('#tdx_competitor_info').attr('disabled', 'disabled');
        }
    }
    
    function genContent() {
        var postTitle = jQuery('#post_title').val();
        if (postTitle) {
            jQuery('.tdx-scrape-result').html('<p>Trigger n8n workflow...</p>');
            
            // Chuẩn bị dữ liệu cho API_Handler::trigger_n8n_webhook
            var postData = {
                'categories': jQuery('#tdx_post_category').val(),
                'author': jQuery('#tdx_post_author').val(),
                'status': jQuery('#tdx_post_status').val(),
                'post_type': jQuery('#tdx_post_type').val(),
                'gen_image': jQuery('#tdx_gen_image').is(':checked')
            };
            
            // Gửi AJAX request
            jQuery.post(ajaxurl, {
                action: '<?php echo TDX_CONST['plugin_prefix_name'].'_trigger_webhook'; ?>',
                post_title: postTitle,
                post_data: postData,
                _ajax_nonce: '<?php echo wp_create_nonce(TDX_CONST['plugin_prefix_name'].'_trigger_webhook'); ?>'
            }, function(response) {
                jQuery('.tdx-scrape-result').html('<p>Đang tạo bài viết, quá trình này có thể mất từ 1-3 phút...</p>');
                // Disable submit button
                jQuery('input[type="submit"]').prop('disabled', true);
            });
        } else {
            jQuery('.tdx-scrape-result').html('<p class="error">Vui lòng nhập tiêu đề bài viết</p>');
        }
        return false;
    }

    function checkWorkflowStatus() {
        jQuery.ajax({
            url: aiPostGeneratorAdmin.ajaxurl,
            type: 'GET',
            data: {
                action: 'check_n8n_callback',
                _ajax_nonce: aiPostGeneratorAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    // Enable submit button
                    jQuery('input[type="submit"]').prop('disabled', false);
                    // Remove the 'Đang tạo bài viết...' message
                    jQuery('.tdx-scrape-result').html('');
                    // Show message
                    alert(data.message);
                }
            }
        });
    }
    setInterval(checkWorkflowStatus, 10000);

</script>