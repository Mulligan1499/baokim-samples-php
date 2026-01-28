# Baokim B2B API - PHP 7 Example

Bộ source code mẫu tích hợp Baokim B2B API, viết bằng PHP thuần (PHP 7.x), không dependencies.

## 🔧 Yêu cầu
- PHP 7.0+
- Extensions: `curl`, `openssl`, `json`

## 📦 Cài đặt

```bash
git clone https://github.com/Mulligan1499/baokim-b2b-php-example.git
cd baokim-b2b-php-example
cp config/config.php config/config.local.php
# Chỉnh sửa config.local.php với thông tin thực
```

## 🚀 Quick Start

```bash
# Test tất cả APIs
php test_full_flow.php

# Test với refund
php test_full_flow.php ORDER_ID AMOUNT
```

## 📁 Cấu trúc

```
├── config/                     # Cấu hình
├── src/                        # Core modules
├── examples/
│   ├── basic_pro/
│   │   ├── 01_get_token.php
│   │   ├── 02_create_order.php
│   │   ├── 03_query_order.php
│   │   ├── 04_refund_order.php
│   │   └── 05_cancel_auto_debit.php
│   ├── va_host_to_host/
│   │   ├── 05_create_va.php
│   │   ├── 06_update_va.php
│   │   └── 07_query_transaction.php
│   └── webhook_receiver.php
├── keys/                       # RSA Keys
├── logs/                       # Log files
└── test_full_flow.php          # Test tất cả APIs
```

## 📚 APIs

### Basic Pro
| API | Endpoint |
|-----|----------|
| Lấy Token | `/b2b/auth-service/api/oauth/get-token` |
| Tạo đơn | `/b2b/core/api/ext/mm/order/send` |
| Tra cứu | `/b2b/core/api/ext/mm/order/get-order` |
| Hoàn tiền | `/b2b/core/api/ext/mm/refund/send` |
| Hủy thu hộ | `/b2b/core/api/ext/mm/autodebit/cancel` |

### VA Host to Host
| API | Endpoint |
|-----|----------|
| Tạo VA | `/b2b/core/api/ext/mm/bank-transfer/create` |
| Cập nhật VA | `/b2b/core/api/ext/mm/bank-transfer/update` |
| Tra cứu VA | `/b2b/core/api/ext/mm/bank-transfer/detail` |

## 🖥️ Replit

Import repo → Tạo `config/config.local.php` → Tạo `keys/merchant_private.pem` → Run

---
© 2026 Baokim
