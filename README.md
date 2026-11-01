# Wellnest Medical Clinic — LAB 2

**Sinh viên:** Huỳnh Đức Phát (2452940) · **Lớp:** CC01

**Giảng viên:** NGUYỄN ĐỨC THÁI

**Đề tài:** T3 — Medical Clinic Website

**Lab:** Database-Driven Web Development, CRUD Operations, and User Authentication

## Mở website

Bật **Apache** và **MySQL** trong XAMPP. Dự án được đặt tại `xamppfiles/htdocs/medical_clinic`.

- Website: **http://localhost/medical_clinic/**
- Đăng ký: http://localhost/medical_clinic/auth/register.php
- Đăng nhập: http://localhost/medical_clinic/auth/login.php
- Bệnh nhân: http://localhost/medical_clinic/pages/dashboard.php
- Admin: **http://localhost/medical_clinic/admin/**
- phpMyAdmin: http://localhost/phpmyadmin/

Website dùng PHP **8.0+**, MariaDB **10.4+** hoặc MySQL **8.0+**, `pdo_mysql`, `mbstring`, `session`. Ảnh, font, Bootstrap và JavaScript có sẵn trong source; không cần Node.js/Composer để chạy website.

## Cài database trên máy khác

1. Chép thư mục `medical_clinic` vào `htdocs`.
2. Bật Apache/MySQL.
3. Mở phpMyAdmin, chọn **Import**, nhập file **`database/lab2.sql`**. File tạo database `wellnest_lab2`, sáu bảng, khóa ngoại, bốn chuyên khoa, sáu dịch vụ, tài khoản và yêu cầu lịch hẹn mẫu.
4. Cấu hình tập trung trong `config/database.php`. Mặc định phù hợp XAMPP cục bộ: host `127.0.0.1`, port `3306`, database `wellnest_lab2`, user `root`, mật khẩu trống. Nếu cấu hình máy khác, chép `config/local.example.php` thành `config/local.php` rồi sửa thông tin. `config/local.php` được loại khỏi Git và gói nộp.
5. Mở website. Dữ liệu không bị seed lại trên mỗi request; thay đổi từ admin hoặc MySQL được giữ nguyên và hiện ra ngay ở frontend.

Trên máy đang thực hiện bài, cấu hình riêng đã dùng tài khoản MySQL **chỉ có SELECT/INSERT/UPDATE/DELETE trên `wellnest_lab2`**, không dùng root trong ứng dụng. Không thay đổi các database khác.

Với macOS XAMPP, nếu Apache chưa có quyền ghi session:

```sh
/Applications/XAMPP/xamppfiles/bin/php tools/setup.php --xampp-acl
```

Lệnh chỉ cấp ACL cho đúng tài khoản Apache `daemon` trên thư mục runtime/session, giữ nguyên quyền source và không dùng `chmod 777`. Trên hệ điều hành khác, cấp quyền ghi `storage/sessions/` cho tài khoản chạy PHP.

Chạy bằng PHP development server nếu cần:

```sh
php -S 127.0.0.1:8080 -t . tools/router.php
```

Router đi kèm chặn cấu hình, database, runtime, Git và tài liệu nội bộ. Với Apache, bật `mod_rewrite` và `AllowOverride All` để `.htaccess` hoạt động.

## Tài khoản demo

| Vai trò | Email | Mật khẩu demo |
| --- | --- | --- |
| Administrator | `admin@wellnest.example` | `WellnestAdmin!2026` |
| Patient | `patient@wellnest.example` | `WellnestPatient!2026` |

Đây là các tài khoản mẫu trong SQL phục vụ chấm bài cục bộ. Mật khẩu được lưu bằng `password_hash()`; `password_verify()` dùng khi đăng nhập. Người đăng ký mới luôn có vai trò **patient**, bất kể giá trị role gửi từ client.

## Tính năng theo specification

1. **Database:** `users`, `categories`, `services`, `appointment_requests`, `audit_logs`, `login_attempts`; InnoDB, utf8mb4, primary keys, unique email, indexes và foreign keys.
2. **PHP/MySQL:** PDO dùng một cấu hình chung, prepared statements, exception handling với thông báo chung cho người dùng; không lộ SQL, mật khẩu hoặc stack trace.
3. **CRUD dịch vụ:** thêm bằng form; danh sách, tìm kiếm/lọc/phân trang và chi tiết; cập nhật đầy đủ; xóa qua trang xác nhận với POST + CSRF. Dịch vụ có lịch hẹn được bảo vệ bởi khóa ngoại, có thể chuyển Draft. Kiểm tra version chống ghi đè khi sửa từ nhiều tab.
4. **Authentication:** đăng ký, email unique, validation tên/email/password/confirmation, đăng nhập, giới hạn thử sai, đổi session ID, dashboard được bảo vệ và logout bằng POST.
5. **Sessions/cookies:** session giữ `user_id`, CSRF và thời gian hoạt động; timeout sau 2 giờ không hoạt động. Cookie `wellnest_sidebar` chỉ chứa `expanded` hoặc `collapsed`, tồn tại 30 ngày, SameSite=Lax và giới hạn path dự án. Cookie session là HttpOnly; Secure khi dùng HTTPS. Không lưu mật khẩu trong cookie.
6. **Phân quyền:** admin quản lý; patient xem và hủy yêu cầu thuộc tài khoản mình. Mọi route admin kiểm tra server, không chỉ ẩn menu. GET xóa và GET logout không thực hiện thay đổi.
7. **Tích hợp:** Published/Draft đồng bộ website; lịch hẹn của bệnh nhân xuất hiện ở admin; trạng thái admin thay đổi hiện lại trong dashboard bệnh nhân. Giá và tên dịch vụ khi gửi yêu cầu được lưu dưới dạng snapshot để không làm sai lịch sử.

Admin có sidebar thu gọn trên desktop, menu trượt trên mobile, overlay, phím Escape và quản lý focus bàn phím. Preferences cho phép xem/đổi và giải thích cookie.

**Phạm vi:** Đây là dự án học tập, không cung cấp khám chữa bệnh, không thu tiền, không gửi email và không đặt lịch khám thực tế. Hãy sử dụng thông tin ví dụ trong form.

## Kiểm thử và report

- `report/lab2/LAB_2_Report_Huynh_Duc_Phat_2452940.pdf`
- `report/lab2/LAB_2_Report_Huynh_Duc_Phat_2452940.docx`
- `report/lab2/screenshots/`: phpMyAdmin, CRUD, registration/login/logout, patient/admin, cookies và responsive.
- `report/lab2/backend-verification.json`, `report/lab2/verification.json`: kết quả kiểm thử.
- `report/lab2/error-handling-verification.json`: xác minh lỗi database trả HTML/JSON HTTP 503, không lộ chi tiết kết nối.
- `report/lab2/evidence-index.json`: đối chiếu **40 ảnh chụp thực tế** với phần và chú thích trong báo cáo; `report/lab2/diagrams/` chứa sơ đồ quan hệ ở định dạng PDF/SVG/PNG.
- `submission/LAB_2_Medical_Clinic_Huynh_Duc_Phat_2452940.zip`: bộ nộp đầy đủ source, SQL, report và ảnh, loại cấu hình riêng/session/QA runtime.

Lần kiểm chứng ngày **01/11/2026** đạt **60 backend checks**, **584 browser assertions**, **208 tổ hợp trang/viewport** trên **16 kích thước 320–2560px** (có màn hình ngang), và **26 lượt axe WCAG A/AA** không báo vi phạm. Bộ kiểm thử dùng Chrome; chưa kiểm chứng trên thiết bị iOS/Android thật hoặc Safari/Firefox. Đây là bằng chứng trong phạm vi đã thử, không phải bảo đảm mọi trình duyệt/màn hình hay điểm chấm của giảng viên.

Backend tests dùng database tạm riêng và tự dọn sau khi chạy (cần quyền tạo database của XAMPP root):

```sh
python3 tools/run_backend_tests.py
```

Browser tests cần Chrome, Node.js, `playwright`, `axe-core` và website XAMPP đang chạy:

```sh
npm install --prefix /tmp/wellnest-qa playwright axe-core
NODE_PATH=/tmp/wellnest-qa/node_modules node tests/browser.cjs
```

Đặt `CLINIC_BASE_URL` hoặc `CHROME_PATH` nếu URL/đường dẫn Chrome khác. Bộ test thực hiện CRUD, đăng ký và demo request bằng dữ liệu mang tiền tố QA. Không sử dụng bộ test với dữ liệu production.

Kiểm tra xử lý lỗi database bằng tiến trình PHP tạm riêng, sau đó dọn các bản ghi QA đã tạo:

```sh
python3 tools/verify_error_handling.py
/Applications/XAMPP/xamppfiles/bin/php tools/cleanup_qa.php
```

Tạo lại báo cáo và gói nộp sau khi kiểm thử đạt:

```sh
python3 -m venv /tmp/wellnest-report-venv
/tmp/wellnest-report-venv/bin/pip install python-docx reportlab Pillow pymupdf
/tmp/wellnest-report-venv/bin/python tools/generate_lab2_report.py
/tmp/wellnest-report-venv/bin/python tools/verify_lab2_deliverables.py
python3 tools/package_submission.py
```

Script kiểm thử backend và script tạo báo cáo hiện dùng đường dẫn PHP/MySQL/font của macOS XAMPP; khi chạy các công cụ QA trên Windows/Linux cần điều chỉnh đường dẫn tương ứng. Source PHP chạy độc lập với các công cụ tạo báo cáo này.

## Các phiên bản trên GitHub

Repository: https://github.com/HelloFunny123/medical_clinic

- **`lab-1`**: website và report LAB 1.3 hoàn chỉnh, commit `066802c`.
- **`lab-2`**: phiên bản MySQL, CRUD, authentication và admin của LAB 2, kèm báo cáo và minh chứng kiểm thử.

Xem Lab 1 mà không thay đổi thư mục đang chạy: mở GitHub, chọn tag `lab-1`. Source Lab 2 tiếp tục mở rộng từ Lab 1 để dùng cho Lab 3. Report Lab 1 vẫn được giữ trong `report/`.
