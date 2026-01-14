# tindi-wp

## 1. Workflow AI Scraper

Scrape lại bài viết từ 1 url nào đó và viết lại content chuẩn SEO bằng AI

#### Tính năng
- **Scrape nội dung từ URL**: Tự động lấy danh sách và nội dung bài viết từ URL được chỉ định
- **Trích xuất HTML thông minh**: Loại bỏ các phần tử không cần thiết (hình ảnh, navigation) và chỉ lấy nội dung text chính
- **Trích xuất tiêu đề**: Tự động lấy tiêu đề bài viết từ HTML
- **Xử lý AI với OpenAI**: Sử dụng GPT-4o để tóm tắt và viết lại nội dung
- **Chia nhỏ văn bản**: Tự động chia nội dung dài thành các chunks (6000 ký tự) để xử lý hiệu quả hơn
- **Giới hạn số lượng**: Có thể giới hạn số lượng bài viết cần xử lý (mặc định 3 bài)
- **Định dạng đầu ra**: Tự động làm sạch và định dạng dữ liệu với title, summary và URL

![AI Scraper Workflow](./n8n/ai_scraper.png)

[ai_scraper.json](./n8n/workflow/ai_scraper.json)

## 2. Workflow Tindi Article

Tự động tạo bài viết SEO hoàn chỉnh từ tiêu đề, bao gồm nội dung, hình ảnh, schema markup và meta tags

#### Tính năng
- **Tạo nội dung chi tiết**: Chia nhỏ dàn ý thành từng phần và AI Agent viết nội dung chi tiết cho từng phần riêng biệt, sau đó tự động merge thành bài viết hoàn chỉnh từ 1700-2000 từ với mật độ từ khóa 5-10%, định dạng Markdown
- **Tạo JSON-LD Schema**: Tự động tạo schema markup (FAQPage, HowTo) theo chuẩn schema.org để tối ưu SEO
- **Tạo hình ảnh AI**: Tự động tạo hình ảnh minh họa bằng Freepik AI API dựa trên nội dung bài viết (tùy chọn)
- **Tối ưu SEO tự động**: Tạo meta title, meta description, permalink và từ khóa phụ với AI SEO Expert
- **Tích hợp RankMath**: Tự động cập nhật meta tags vào RankMath SEO plugin
- **Đánh số tiêu đề tự động**: Tự động đánh số lại các heading (H2, H3) theo thứ tự logic
- **Đăng bài tự động**: Tự động đăng bài viết lên WordPress với status, author, categories được cấu hình
- **Set ảnh đại diện**: Tự động đặt ảnh đầu tiên làm featured image
- **Callback thông báo**: Gửi callback về WordPress để thông báo kết quả

![Tindi Article Workflow](./n8n/tindi_article.png)

[tindi_article.json](./n8n/workflow/tindi_article.json)

## 3. Workflow chatbot + zalo

Chatbot tích hợp zalo để tự động phản hồi và tổng hợp thông tin + nhu cầu của khách hàng

#### Tính năng
- **Tự động trigger**: Tự động kích hoạt workflow khi có tin nhắn từ tài khoản cá nhân hoặc khi nhận được tin nhắn từ khách hàng
- **Tự động dừng khi người dùng reply**: Khi người dùng Zalo reply tin nhắn của khách hàng, workflow sẽ tự động dừng lại để tránh phản hồi trùng lặp
- **Quản lý timestamp với Redis**: Sử dụng Redis queue để lưu trữ timestamp của message, giúp xử lý tin nhắn theo thứ tự thời gian và phát hiện khi người dùng đã reply (so sánh timestamp để quyết định dừng workflow)
- **Database** (mở rộng): Sử dụng postgres của supabase để lưu trữ message cho AI Agent
- **Tích hợp Google Sheet**: Tự động tổng hợp câu hỏi và câu trả lời vào Google Sheet để cung cấp context cho AI xử lý