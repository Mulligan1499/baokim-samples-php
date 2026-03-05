# Baokim B2B PHP Example - Replit Setup Guide

## 🚀 Cách chạy trên Replit

### Bước 1: Import từ GitHub
1. Vào [Replit](https://replit.com)
2. Click **Create Repl** → **Import from GitHub**
3. Paste URL: `https://github.com/Mulligan1499/baokim-b2b-php-example`
4. Chọn **Language**: PHP CLI

### Bước 2: Cấu hình
Mở file `src/config/config.php` và điền thông tin:

```php
<?php
return [
    'base_url' => 'https://devtest.baokim.vn',
    'timeout' => 30,
    'merchant_code' => 'b2bthiither127',
    'client_id' => 'mmthiither22',
    'client_secret' => 'vpp6yTe4%BbnMdP7it2Dz0x3IOzrxUVu',
    'master_merchant_code' => 'mmthiither22',
    'sub_merchant_code' => 'b2bthiither127',
    'merchant_private_key_path' => __DIR__ . '/../keys/merchant_private.pem',
    'baokim_public_key_path' => __DIR__ . '/../keys/baokim_public.pem',
    'url_success' => 'https://webhook.site/2f5fd254-547f-4238-a251-af771b61bf44',
    'url_fail' => 'https://webhook.site/2f5fd254-547f-4238-a251-af771b61bf44',
    'webhook_url' => 'https://webhook.site/2f5fd254-547f-4238-a251-af771b61bf44',
];
```

### Bước 3: Tạo Private Key
Tạo file `keys/merchant_private.pem` với nội dung private key.

### Bước 4: Chạy test
Trong Replit Shell, chạy các lệnh:

```bash
# Test lấy token
php examples/basic_pro/01_get_token.php

# Test tạo đơn
php examples/basic_pro/02_create_order.php

# Test tra cứu đơn
php examples/basic_pro/03_query_order.php ORDER_ID

# Test hoàn tiền
php examples/basic_pro/04_refund_order.php ORDER_ID AMOUNT "Reason"

# Test hủy thu hộ tự động
php examples/basic_pro/05_cancel_auto_debit.php TOKEN

# Test tạo VA
php examples/va_host_to_host/05_create_va.php

# Test tra cứu VA
php examples/va_host_to_host/07_query_transaction.php VA_NUMBER
```

## 📁 Cấu trúc thư mục

```
├── config/
│   └── config.php           # Config thực (chỉnh sửa trực tiếp)
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
├── keys/
│   └── merchant_private.pem  # Private key (tạo thủ công)
├── logs/                     # Log files
└── src/                      # Source code
```
