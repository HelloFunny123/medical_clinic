# Wellnest Medical Clinic — LAB 1.3

**Sinh viên:** Huỳnh Đức Phát (2452940) · **Lớp:** CC01  
**Giảng viên:** NGUYỄN ĐỨC THÁI  
**Đề tài:** Medical Clinic · **Lab:** Website Layout & Frontend Foundation

## Mở website

Với dự án ở `xamppfiles/htdocs/medical_clinic`, bật Apache trong XAMPP rồi mở:

**http://localhost/medical_clinic/**

Trang chủ: `public/index.php`. Trang chi tiết: `pages/item_details.php?id=1` (ID 1–6).

Yêu cầu PHP **8.0+**, extension `pdo_sqlite`, `mbstring`, `session` (đã có trong XAMPP được kiểm tra). Không cần Node.js, Composer, MySQL hay kết nối Internet để chạy website. Dữ liệu dịch vụ tự khởi tạo từ `app/services.php` vào SQLite. Bootstrap 5.3.0 được liên kết qua CDN theo specification và có bản cục bộ đi kèm; ảnh, font và JavaScript đều lưu cục bộ.

Nếu cài lại trên macOS XAMPP, chạy tại thư mục dự án:

```sh
/Applications/XAMPP/xamppfiles/bin/php tools/setup.php --xampp-acl
```

Lệnh khởi tạo database và cấp ACL **riêng cho tài khoản Apache `daemon`** trên thư mục runtime/file SQLite. Không đổi quyền source và không mở quyền ghi cho mọi tài khoản. Với PHP chạy bằng tài khoản hiện tại, chỉ cần `php tools/setup.php`. Trên hệ điều hành khác, cấp quyền ghi `storage/` cho đúng tài khoản chạy PHP.

Cách chạy thay thế bằng PHP development server:

```sh
php tools/setup.php
php -S 127.0.0.1:8080 -t . tools/router.php
```

Sau đó mở http://127.0.0.1:8080/. Dùng router đi kèm để chặn các thư mục nội bộ. Apache sử dụng `.htaccess` với `mod_rewrite` và `AllowOverride All`.

## Nội dung đúng specification

- Cấu trúc thư mục `public/`, `pages/`, `components/` đúng yêu cầu.
- HTML5 semantic, meta viewport, tiêu đề riêng cho từng trang.
- Bootstrap navbar có Home, Items / Services, Search, Contact; hamburger trên mobile.
- Hero, 6 thẻ dịch vụ, footer có thương hiệu, copyright và thông tin liên hệ minh họa.
- Grid `col-12 col-md-6 col-lg-4`: 1 / 2 / 3 cột theo breakpoint Bootstrap.
- CSS riêng: font, màu sắc, spacing, cards, buttons, hero, footer, hover, reduced motion.
- Header/footer tái sử dụng bằng PHP `require_once`.
- Chi tiết cho cả 6 dịch vụ: tên, ảnh, chuyên khoa, mô tả, giá và nút hành động.
- JavaScript: tìm kiếm, lọc chuyên khoa, trạng thái rỗng, lưu dịch vụ với localStorage và thông báo trực quan.
- Mở rộng: yêu cầu lịch hẹn demo có validation cả client/server, CSRF, giá lấy từ database, chống ghi trùng khi gửi lại, xác nhận sau khi lưu. Form vẫn dùng được khi tắt JavaScript.

**Phạm vi:** LAB 1.3 là frontend foundation. SQLite/PHP bổ sung giúp danh sách, chi tiết và yêu cầu demo đồng bộ. Đây là phòng khám giả lập phục vụ học tập: không cung cấp khám chữa bệnh, không gửi email, không thu tiền và không xác nhận lịch khám thực tế. Hãy sử dụng thông tin liên hệ ví dụ trong form.

## Report và hồ sơ nộp

Trong `report/`:

- `LAB_1_3_Report_Huynh_Duc_Phat_2452940.pdf`: bản nộp PDF.
- `LAB_1_3_Report_Huynh_Duc_Phat_2452940.docx`: bản Word có thể chỉnh sửa.
- `screenshots/`: ảnh chụp cấu trúc dự án, homepage desktop/mobile/tablet, chi tiết, tương tác JavaScript và form demo.
- `verification.json`: kết quả kiểm thử được giữ kèm báo cáo.

Gói nộp hoàn chỉnh: `submission/LAB_1_3_Medical_Clinic_Huynh_Duc_Phat_2452940.zip`. ZIP chứa toàn bộ source, report và ảnh minh chứng; không đóng gói session hay dữ liệu lịch hẹn thử nghiệm. Database dịch vụ sẽ tự khởi tạo khi chạy.

Report chỉ trình bày các mục lab yêu cầu: cấu trúc, homepage, catalog, details, responsive, JavaScript, accessibility, checklist và ảnh minh chứng.

## Kiểm tra lại

Backend (test dùng SQLite tạm riêng, không sửa dữ liệu website):

```sh
/Applications/XAMPP/xamppfiles/bin/php tests/backend.php
```

Kiểm thử trình duyệt cần Node.js, `playwright`, `axe-core`, Chrome và website đang chạy. Các công cụ này chỉ dùng lúc kiểm thử, không phải dependency của website:

```sh
npm install --prefix /tmp/wellnest-qa playwright axe-core
NODE_PATH=/tmp/wellnest-qa/node_modules node tests/browser.cjs
```

Có thể đặt `CHROME_PATH` nếu đường dẫn Chrome khác. `CLINIC_BASE_URL` mặc định là `http://127.0.0.1:8080`, có thể đổi thành `http://localhost/medical_clinic`.

Tái tạo report bằng `tools/generate_report.py` (cần `python-docx`, `reportlab`, `Pillow`), sau khi chạy browser tests. Ảnh/font/thư viện có thông tin nguồn và giấy phép tại `public/images/CREDITS.md` và `public/vendor/`.
