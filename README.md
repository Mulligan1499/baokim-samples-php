# Baokim B2B API - PHP7 Example Code

Bộ source code mẫu để tích hợp với hệ thống B2B của Baokim, viết bằng PHP thuần (PHP 7.x), không sử dụng thư viện bên ngoài.

## 📋 Mục lục

- [Yêu cầu hệ thống](#-yêu-cầu-hệ-thống)
- [Cài đặt](#-cài-đặt)
- [Cấu trúc thư mục](#-cấu-trúc-thư-mục)
- [Quick Start](#-quick-start)
- [API Reference](#-api-reference)
- [Mã lỗi](#-mã-lỗi)

## 🔧 Yêu cầu hệ thống

- **PHP**: 7.0 trở lên
- **Extensions bắt buộc**:
  - `curl` - Gọi HTTP requests
  - `openssl` - Ký số RSA SHA256
  - `json` - Parse JSON

## 📦 Cài đặt

### 1. Clone repository

```bash
git clone https://github.com/Mulligan1499/baokim-b2b-php-example.git
cd baokim-b2b-php-example
```

### 2. Tạo file cấu hình

```bash
cp config/config.php config/config.local.php
```

Chỉnh sửa `config/config.local.php` với thông tin được Baokim cung cấp:

```php
return [
    'base_url' => 'https://devtest.baokim.vn',  // hoặc https://openapi.baokim.vn
    'merchant_code' => 'YOUR_MERCHANT_CODE',
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'master_merchant_code' => 'YOUR_MASTER_MERCHANT_CODE',
    'sub_merchant_code' => 'YOUR_SUB_MERCHANT_CODE',
    'merchant_private_key_path' => __DIR__ . '/../keys/merchant_private.pem',
    'baokim_public_key_path' => __DIR__ . '/../keys/baokim_public.pem',
    'url_success' => 'https://your-domain.com/payment/success',
    'url_fail' => 'https://your-domain.com/payment/fail',
    'webhook_url' => 'https://your-domain.com/webhook/baokim',
];
```

### 3. Cấu hình RSA Keys

Đặt **private key** vào `keys/merchant_private.pem`

## 📁 Cấu trúc thư mục

```
baokim-b2b-php-example/
├── config/
│   ├── config.php              # Config mẫu
│   └── config.local.php        # Config thực (không commit)
├── src/
│   ├── autoload.php            # PSR-4 Autoloader
│   ├── Config.php              # Quản lý cấu hình
│   ├── SignatureHelper.php     # Ký số RSA SHA256
│   ├── HttpClient.php          # HTTP Client với logging
│   ├── Logger.php              # Ghi log request/response
│   ├── BaokimAuth.php          # Xác thực OAuth2
│   ├── BaokimOrder.php         # API Basic Pro
│   ├── BaokimVA.php            # API VA Host to Host
│   ├── WebhookHandler.php      # Xử lý webhook
│   └── ErrorCode.php           # Mapping mã lỗi
├── examples/
│   ├── basic_pro/              # Basic Pro APIs
│   │   ├── 01_get_token.php
│   │   ├── 02_create_order.php
│   │   ├── 03_query_order.php
│   │   ├── 04_refund_order.php
│   │   └── 05_cancel_auto_debit.php
│   ├── va_host_to_host/        # VA H2H APIs
│   │   ├── 05_create_va.php
│   │   ├── 06_update_va.php
│   │   └── 07_query_transaction.php
│   └── webhook_receiver.php
├── keys/                       # RSA Keys
├── logs/                       # Log files
├── test_full_flow.php          # Test tất cả APIs
├── .replit                     # Replit config
└── replit.nix                  # Replit dependencies
```

## 🚀 Quick Start

### Chạy test toàn bộ APIs

```bash
php test_full_flow.php
```

Kết quả:
```
╔══════════════════════════════════════════════════════════╗
║       BAOKIM B2B API - FULL TEST FLOW                    ║
╚══════════════════════════════════════════════════════════╝

📍 [1/6] LẤY ACCESS TOKEN ✅
📍 [2/6] TẠO ĐƠN HÀNG ✅
📍 [3/6] TRA CỨU ĐƠN HÀNG ✅
📍 [4/6] TẠO DYNAMIC VA ✅
📍 [5/6] TRA CỨU GIAO DỊCH VA ✅
📍 [6/6] HOÀN TIỀN (optional)
```

### Test với refund

```bash
php test_full_flow.php ORDER_ID AMOUNT
# Ví dụ: php test_full_flow.php ORDER_20260128_1234 100000
```

### Chạy từng API riêng lẻ

```bash
# Basic Pro
php examples/basic_pro/01_get_token.php
php examples/basic_pro/02_create_order.php
php examples/basic_pro/03_query_order.php ORDER_ID
php examples/basic_pro/04_refund_order.php ORDER_ID AMOUNT "Reason"
php examples/basic_pro/05_cancel_auto_debit.php TOKEN

# VA Host to Host
php examples/va_host_to_host/05_create_va.php
php examples/va_host_to_host/06_update_va.php VA_NUMBER
php examples/va_host_to_host/07_query_transaction.php VA_NUMBER
```

## 📚 API Reference

### Basic Pro APIs

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/b2b/auth-service/api/oauth/get-token` | POST | Lấy access token |
| `/b2b/core/api/ext/mm/order/send` | POST | Tạo đơn hàng |
| `/b2b/core/api/ext/mm/order/get-order` | POST | Tra cứu đơn hàng |
| `/b2b/core/api/ext/mm/refund/send` | POST | Hoàn tiền |
| `/b2b/core/api/ext/mm/autodebit/cancel` | POST | Hủy thu hộ tự động |

### VA Host to Host APIs

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/b2b/core/api/ext/mm/bank-transfer/create` | POST | Tạo VA |
| `/b2b/core/api/ext/mm/bank-transfer/update` | POST | Cập nhật VA |
| `/b2b/core/api/ext/mm/bank-transfer/detail` | POST | Tra cứu giao dịch |
| `/b2b/core/api/ext/mm/refund/send` | POST | Hoàn tiền giao dịch VA |

## ❌ Mã lỗi

| Code | Mô tả |
|------|-------|
| `0` | Thành công |
| `100` | Đang xử lý / Thành công |
| `101` | Thành công - Cần redirect |
| `103` | Chữ ký số không hợp lệ |
| `200` | Thành công |
| `422` | Dữ liệu không hợp lệ |

## �️ Chạy trên Replit

1. Import repo từ GitHub
2. Tạo `config/config.local.php`
3. Tạo `keys/merchant_private.pem`
4. Click **Run**

Chi tiết xem file `REPLIT_SETUP.md`

## 📝 Logs

Tất cả request/response được log vào `logs/api_YYYY-MM-DD.log`

---

© 2026 Baokim. All rights reserved.
