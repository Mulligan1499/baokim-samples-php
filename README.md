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
```

Chỉnh sửa `config/config.local.php` với thông tin Baokim cung cấp:
- `client_id`, `client_secret` - Thông tin OAuth2
- `merchant_code`, `master_merchant_code`, `sub_merchant_code`
- Đặt file `merchant_private.pem` vào thư mục `keys/`

## 🚀 Quick Start

```bash
# Test tất cả APIs
php test_full_flow.php

# Test từng loại connection
php test_full_flow.php basic_pro
php test_full_flow.php host_to_host
php test_full_flow.php direct
```

---

## 📖 Hướng dẫn sử dụng

### Bước 1: Include autoload
```php
require_once __DIR__ . '/src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MasterSub\BaokimOrder;
use Baokim\B2B\HostToHost\BaokimVA;
use Baokim\B2B\Direct\BaokimDirect;

// Load config
Config::load(__DIR__ . '/config/config.local.php');
```

### Bước 2: Khởi tạo Authentication
```php
// Lấy token (tự động cache, không cần gọi lại)
$auth = new BaokimAuth();
$token = $auth->getToken();
```

---

## 🔷 Basic/Pro - Thanh toán qua Master/Sub Merchant

**Class:** `BaokimOrder`

### Tạo đơn hàng
```php
$orderService = new BaokimOrder($auth);

$result = $orderService->createOrder([
    'mrc_order_id' => 'ORDER_' . time(),      // Mã đơn hàng của bạn (bắt buộc)
    'total_amount' => 100000,                  // Số tiền (bắt buộc)
    'description' => 'Thanh toán đơn hàng',    // Mô tả (bắt buộc)
    'payment_method' => 1,                     // 1=VA, 6=VNPay QR (tùy chọn)
]);

if ($result['success']) {
    $paymentUrl = $result['data']['payment_url'];
    echo "Chuyển khách hàng đến: $paymentUrl";
}
```

### Tra cứu đơn hàng
```php
$result = $orderService->queryOrder('ORDER_123456');
```

### Hoàn tiền
```php
$result = $orderService->refundOrder([
    'order_id' => 123456,        // order_id từ Baokim
    'refund_amount' => 50000,    // Số tiền hoàn
    'description' => 'Hoàn tiền cho khách',
]);
```

### Thu hộ tự động (Auto Debit)
```php
$result = $orderService->createAutoDebitOrder([
    'mrc_order_id' => 'AD_' . time(),
    'total_amount' => 200000,
    'description' => 'Thu hộ tự động',
    'phone_no' => '0901234567',
]);
```

---

## 🔷 Host-to-Host - Virtual Account (VA)

**Class:** `BaokimVA`

### Tạo VA động (mỗi đơn hàng 1 VA riêng)
```php
$vaService = new BaokimVA($auth);

$result = $vaService->createDynamicVA(
    'NGUYEN VAN A',           // Tên khách hàng
    'ORDER_123',              // Mã đơn hàng
    100000                    // Số tiền cần thu
);

if ($result['success']) {
    echo "Số VA: " . $result['data']['acc_no'];
    echo "QR Code: " . $result['data']['qr_path'];
}
```

### Tạo VA tĩnh (1 VA dùng nhiều lần)
```php
$result = $vaService->createStaticVA(
    'TRAN VAN B',                    // Tên khách hàng
    'CUSTOMER_001',                  // Mã định danh khách
    '2026-12-31 23:59:59',           // Ngày hết hạn
    10000,                           // Số tiền tối thiểu
    10000000                         // Số tiền tối đa
);
```

### Tra cứu giao dịch VA
```php
$result = $vaService->queryTransaction([
    'acc_no' => '00812345678901',    // Số VA
]);
```

---

## 🔷 Direct Connection - Không qua Master Merchant

**Class:** `BaokimDirect`

> ⚠️ Direct connection cần credentials riêng, cấu hình trong `direct_client_id`, `direct_client_secret`

### Khởi tạo với Direct credentials
```php
$directAuth = BaokimAuth::forDirectConnection();
$directService = new BaokimDirect($directAuth);
```

### Tạo đơn hàng Direct
```php
$result = $directService->createOrder([
    'mrc_order_id' => 'DRT_' . time(),
    'total_amount' => 150000,
    'description' => 'Thanh toán Direct',
    'customer_info' => [
        'name' => 'NGUYEN VAN A',
        'email' => 'customer@email.com',
        'phone' => '0901234567',
        'address' => '123 Nguyen Hue, HCM',
        'gender' => 1,
    ],
]);

if ($result['success']) {
    echo "Payment URL: " . $result['data']['payment_url'];
}
```

### Tra cứu đơn hàng
```php
$result = $directService->queryOrder('DRT_123456');
```

---

## 🔔 Webhook - Nhận thông báo từ Baokim

```php
// webhook_receiver.php
require_once __DIR__ . '/src/autoload.php';

use Baokim\B2B\SignatureHelper;

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_SIGNATURE'] ?? '';

// Verify signature
if (SignatureHelper::verify($rawBody, $signature)) {
    $data = json_decode($rawBody, true);
    
    // Xử lý thông báo
    $orderId = $data['mrc_order_id'];
    $status = $data['status'];
    
    // Cập nhật trạng thái đơn hàng trong hệ thống của bạn
    // ...
    
    echo json_encode(['code' => 0, 'message' => 'OK']);
} else {
    http_response_code(400);
    echo json_encode(['code' => 1, 'message' => 'Invalid signature']);
}
```

---

## 📁 Cấu trúc thư mục

```
├── config/                     # Cấu hình
│   ├── config.php              # Template
│   └── config.local.php        # Config thực (không commit)
├── src/                        # Core modules
│   ├── MasterSub/              # Basic/Pro APIs
│   │   └── BaokimOrder.php
│   ├── HostToHost/             # VA Host-to-Host APIs
│   │   └── BaokimVA.php
│   └── Direct/                 # Direct Connection APIs
│       └── BaokimDirect.php
├── keys/                       # RSA Keys
│   └── merchant_private.pem    # Private key của bạn
├── logs/                       # Log files
└── test_full_flow.php          # Test tất cả APIs
```

## 📚 API Endpoints

### Basic Pro (Master/Sub)
| API | Endpoint |
|-----|----------|
| Tạo đơn | `/b2b/core/api/ext/mm/order/send` |
| Tra cứu | `/b2b/core/api/ext/mm/order/get-order` |
| Hoàn tiền | `/b2b/core/api/ext/mm/refund/send` |

### VA Host to Host
| API | Endpoint |
|-----|----------|
| Tạo VA | `/b2b/core/api/ext/mm/bank-transfer/create` |
| Cập nhật VA | `/b2b/core/api/ext/mm/bank-transfer/update` |
| Tra cứu VA | `/b2b/core/api/ext/mm/bank-transfer/detail` |

### Direct Connection
| API | Endpoint |
|-----|----------|
| Tạo đơn | `/b2b/core/api/ext/order/send` |
| Tra cứu | `/b2b/core/api/ext/order/get-order` |
| Hủy đơn | `/b2b/core/api/ext/order/cancel` |

---

## ❓ Troubleshooting

| Lỗi | Nguyên nhân | Cách sửa |
|-----|-------------|----------|
| `Chữ ký số không hợp lệ` | Private key không đúng | Kiểm tra file `keys/merchant_private.pem` |
| `Token expired` | Token hết hạn | SDK tự động refresh, không cần xử lý |
| `Invalid merchant_code` | Sai mã merchant | Kiểm tra config |

---
© 2026 Baokim
