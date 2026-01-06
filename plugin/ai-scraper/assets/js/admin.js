jQuery(document).ready(function($) {
    // Khởi tạo các chức năng chung
    console.log('AI Scraper Plugin - Admin JS loaded');

    // Thêm HTML cho phần hiển thị số dư và modal
    $('body.toplevel_page_scraper-plugin #wpcontent, body.ai-scraper_page_scraper-plugin-settings #wpcontent, body.ai-scraper_page_scraper-plugin-logs #wpcontent').prepend(`
        <div id="ai-scraper-balance" style="
            position: fixed;
            top: 32px;
            right: 0;
            background: #fff;
            padding: 10px 15px;
            border-radius: 4px 0 0 4px;
            box-shadow: -2px 2px 4px rgba(0,0,0,0.1);
            z-index: 9999;
            transition: transform 0.3s ease;
        ">
            <div style="
                font-size: 13px;
                color: #2271b1;
                font-weight: 600;
                margin-bottom: 8px;
                border-bottom: 1px solid #f0f0f1;
                padding-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 15px;
            ">
                <button id="toggle-balance" class="button button-small" style="padding: 0 2px; min-height: 20px;">
                    <span class="dashicons dashicons-arrow-right" style="margin: 2px 0 0 0;"></span>
                </button>
                <div>
                    <span class="dashicons dashicons-admin-site-alt3" style="margin: 0 5px 0 -2px;"></span>
                    <span id="plugin-name">...</span>
                </div>
            </div>
            <div id="balance-content" style="
                display: flex;
                align-items: center;
                gap: 15px;
                min-width: 250px;
            ">
                <div>
                    <span class="dashicons dashicons-money-alt" style="color: #2271b1; margin-right: 5px;"></span>
                    Số dư: <span id="balance-amount">...</span>
                    <div id="balance-status" style="font-size: 12px; margin-top: 3px;"></div>
                </div>
                <button id="topup-button" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="margin: 4px 5px 0 -2px;"></span>
                    Nạp tiền
                </button>
            </div>
        </div>

        <!-- Modal nạp tiền -->
        <div id="topup-modal" style="
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 99999;
            overflow-y: auto;
            padding: 20px;
        ">
            <div style="
                position: relative;
                width: 90%;
                max-width: 600px;
                min-height: 200px;
                max-height: 600px;
                margin: 30px auto;
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            ">
                <button id="close-modal" class="button" style="
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
                <h2 style="margin-top: 0;">Nạp tiền</h2>
                <div id="topup-content" style="
                    max-height: calc(600px - 100px);
                    overflow-y: auto;
                    padding-right: 10px;
                ">Đang tải...</div>
            </div>
        </div>
    `);

    // Khởi tạo trạng thái từ localStorage
    const isBalanceVisible = localStorage.getItem('aiScraperBalanceVisible') !== 'false';
    if (!isBalanceVisible) {
        $('#balance-content').hide();
        $('#toggle-balance .dashicons').removeClass('dashicons-arrow-right').addClass('dashicons-arrow-left');
        $('#ai-scraper-balance').css('transform', 'translateX(calc(100% - 40px))');
    }

    // Xử lý sự kiện toggle
    $('#toggle-balance').on('click', function() {
        const $icon = $(this).find('.dashicons');
        const $content = $('#balance-content');
        const $container = $('#ai-scraper-balance');
        
        if ($content.is(':visible')) {
            $content.hide();
            $icon.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-left');
            $container.css('transform', 'translateX(calc(100% - 40px))');
            localStorage.setItem('aiScraperBalanceVisible', 'false');
        } else {
            $content.show();
            $icon.removeClass('dashicons-arrow-left').addClass('dashicons-arrow-right');
            $container.css('transform', 'translateX(0)');
            localStorage.setItem('aiScraperBalanceVisible', 'true');
        }
    });

    // Hàm lấy và cập nhật số dư
    function updateBalance() {
        $.ajax({
            url: aiScraperAdmin.ajaxurl,
            type: 'GET',
            data: {
                action: 'api_remain_credits',
                _ajax_nonce: aiScraperAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#balance-amount').text(data.balance);
                    $('#plugin-name').text(data.plugin_name || 'AI Scraper');
                    
                    // Cập nhật trạng thái
                    const status = data.active ? 
                        '<span style="color: #00a32a;">✓ Đang hoạt động</span>' : 
                        '<span style="color: #d63638;">✗ Không hoạt động</span>';
                    $('#balance-status').html(status);
                } else {
                    $('#balance-amount').text('Lỗi');
                    $('#plugin-name').text('AI Scraper');
                    $('#balance-status').html('<span style="color: #d63638;">Không thể tải số dư</span>');
                }
            },
            error: function() {
                $('#balance-amount').text('Lỗi');
                $('#plugin-name').text('AI Scraper');
                $('#balance-status').html('<span style="color: #d63638;">Không thể kết nối</span>');
            }
        });
    }

    // Hàm lấy nội dung form nạp tiền
    function loadTopupContent() {
        $.ajax({
            url: aiScraperAdmin.ajaxurl,
            type: 'GET',
            data: {
                action: 'api_topup',
                _ajax_nonce: aiScraperAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.topup_content) {
                    $('#topup-content').html(response.data.topup_content ?? 'Không thể tải nội dung nạp tiền');
                } else {
                    $('#topup-content').html('<p style="color: #d63638;">Không thể tải nội dung nạp tiền</p>');
                }
            },
            error: function() {
                $('#topup-content').html('<p style="color: #d63638;">Không thể kết nối đến server</p>');
            }
        });
    }

    // Xử lý sự kiện click nút nạp tiền
    $('#topup-button').on('click', function() {
        $('#topup-modal').fadeIn(200);
        loadTopupContent();
    });

    // Xử lý sự kiện đóng modal
    $('#close-modal, #topup-modal').on('click', function(e) {
        if (e.target === this) {
            $('#topup-modal').fadeOut(200);
        }
    });

    // Cập nhật số dư ngay lập tức khi trang tải xong
    updateBalance();

    // Thiết lập cập nhật tự động mỗi 30 giây
    setInterval(updateBalance, 30000);

    // Xử lý các sự kiện chung
    $('.ai-scraper-admin-page').on('click', '.refresh-button', function(e) {
        e.preventDefault();
        // Thêm logic refresh ở đây
        console.log('Refresh button clicked');
    });

    // Thêm các chức năng khác ở đây
}); 