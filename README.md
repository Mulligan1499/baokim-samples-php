# Baokim B2B API - PHP 7 SDK

Bộ SDK tích hợp Baokim B2B API, viết bằng PHP thuần (PHP 7.x), không dependencies.

## 🔧 Yêu cầu
- PHP 7.0+
- Extensions: `curl`, `openssl`, `json`

---

## 📦 Tích hợp vào project của bạn

### Bước 1: Clone SDK

```bash
git clone https://github.com/ITBaoKim/baokim-samples-php.git
```

### Bước 2: Copy thư mục `src/` vào project

```bash
cp -r baokim-samples-php/src /path/to/your-project/baokim-sdk
```

Thư mục `src/` đã bao gồm sẵn config và keys, bạn chỉ cần copy 1 folder duy nhất:

```
your-project/
├── baokim-sdk/           # Chỉ cần copy folder src/ này
│   ├── autoload.php
│   ├── BaokimAuth.php
│   ├── Config.php
│   ├── HttpClient.php
│   ├── config/           # ← Config nằm sẵn trong SDK
│   │   ├── config.php    # Template config
│   │   └── config.local.php  # Config thật (bạn tạo ở bước 3)
│   ├── keys/             # ← Keys nằm sẵn trong SDK
│   │   └── merchant_private.pem
│   ├── MasterSub/
│   │   └── BaokimOrder.php
│   ├── HostToHost/
│   │   └── BaokimVA.php
│   └── Direct/
│       └── BaokimDirect.php
├── logs/                 # Thư mục log (tự tạo)
└── your-code.php
```

### Bước 3: Cấu hình

1. Copy file config template:
```bash
cp baokim-sdk/config/config.php baokim-sdk/config/config.local.php
```

2. Mở `baokim-sdk/config/config.local.php` và điền thông tin Baokim cung cấp:
```php
<?php
return [
    'base_url' => 'https://devtest.baokim.vn',  // hoặc https://openapi.baokim.vn
    'timeout' => 30,
    
    'merchant_code' => 'YOUR_MERCHANT_CODE',
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    
    'master_merchant_code' => 'YOUR_MASTER_MERCHANT_CODE',
    'sub_merchant_code' => 'YOUR_SUB_MERCHANT_CODE',
    
    // RSA Keys (đường dẫn tương đối từ thư mục config/)
    'merchant_private_key_path' => __DIR__ . '/../keys/merchant_private.pem',
    'baokim_public_key_path' => __DIR__ . '/../keys/baokim_public.pem',
    
    // Webhook URLs
    'url_success' => 'https://your-domain.com/payment/success',
    'url_fail' => 'https://your-domain.com/payment/fail',
    'webhook_url' => 'https://your-domain.com/webhook/baokim',
];
```

### Bước 4: Đặt RSA Keys

Đặt file Private Key (Baokim cung cấp) vào `baokim-sdk/keys/`:
```bash
# Copy merchant_private.pem vào baokim-sdk/keys/
```

### Bước 5: Tạo thư mục logs

```bash
mkdir -p logs
```

---

## 🚀 Sử dụng SDK

### Khởi tạo (bắt buộc ở đầu mỗi file)

```php
<?php
require_once __DIR__ . '/baokim-sdk/autoload.php';

use Baokim\B2B\BaokimAuth;

// Khởi tạo Auth
$auth = new BaokimAuth();
```

> **💡 Ghi chú về Token:**  
> Mỗi lần `new BaokimAuth()` sẽ tạo một instance mới. Khi gọi API lần đầu, SDK tự động lấy token từ Baokim.  
> Trong cùng 1 script, nếu bạn truyền cùng `$auth` cho nhiều service, token sẽ được tái sử dụng (không gọi API lại).  
> Nếu bạn cần tối ưu performance cho production (ví dụ: cache token vào Redis/Session), hãy liên hệ Baokim để được hỗ trợ.

---

## 🔷 API 1: Lấy Access Token

```php
<?php
require_once __DIR__ . '/baokim-sdk/autoload.php';

use Baokim\B2B\BaokimAuth;

$auth = new BaokimAuth();
$token = $auth->getToken();

echo "Token: " . substr($token, 0, 50) . "...\n";
echo "Hết hạn lúc: " . date('Y-m-d H:i:s', $auth->getTokenInfo()['expired_at']) . "\n";
```

```bash
php 01_get_token.php
```

---

## 🔷 API 2: Tạo đơn hàng (Basic Pro - Master/Sub)

```php
<?php
require_once __DIR__ . '/baokim-sdk/autoload.php';

use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MasterSub\BaokimOrder;

$auth = new BaokimAuth();
$token = $auth->getToken();
$orderService = new BaokimOrder($token);

$mrcOrderId = 'ORDER_' . date('YmdHis') . '_' . rand(1000, 9999);

$result = $orderService->createOrder([
    'mrc_order_id' => $mrcOrderId,
    'total_amount' => 100000,
    'description' => 'Thanh toan don hang ' . $mrcOrderId,
    'customer_info' => BaokimOrder::buildCustomerInfo(
        'Nguyen Van A', 'test@email.com', '0901234567', '123 Street'
    )
    ]);

echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
if ($result['success'] && isset($result['data']['payment_url'])) {
    echo "Payment URL: " . $result['data']['payment_url'] . "\n";
}
print_r($result);
```

```bash
php 02_create_order.php
```

---

## 🔷 API 3: Tra cứu đơn hàng

```php
<?php
require_once __DIR__ . '/baokim-sdk/autoload.php';

use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MasterSub\BaokimOrder;

$auth = new BaokimAuth();
$token = $auth->getToken();
$orderService = new BaokimOrder($token);

$mrcOrderId = $argv[1] ?? 'ORDER_TEST';
$result = $orderService->queryOrder($mrcOrderId);

echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
print_r($result);
```

```bash
php 03_query_order.php ORDER_20260224120000_1234
```

---

## 🔷 API 4: Hoàn tiền (Refund)

```php
<?php
require_once __DIR__ . '/baokim-sdk/autoload.php';

use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MasterSub\BaokimOrder;

$auth = new BaokimAuth();
$token = $auth->getToken();
$orderService = new BaokimOrder($token);

$mrcOrderId = $argv[1] ?? 'ORDER_TEST';
$refundAmount = isset($argv[2]) ? (int)$argv[2] : 0;

$result = $orderService->refundOrder($mrcOrderId, $refundAmount, 'Hoan tien cho khach');

echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
print_r($result);
```

```bash
php 04_refund_order.php ORDER_ID 50000
```

---

## 🔷 API 5: Tạo Virtual Account - VA (Host-to-Host)

```php
<?php
require_once __DIR__ . '/baokim-sdk/autoload.php';

use Baokim\B2B\BaokimAuth;
use Baokim\B2B\HostToHost\BaokimVA;

$auth = new BaokimAuth();
$token = $auth->getToken();
$vaService = new BaokimVA($token);

$orderId = 'VA_' . date('YmdHis');

$result = $vaService->createDynamicVA(
    'NGUYEN VAN A',    // Tên khách hàng
    $orderId,          // Mã đơn hàng
    100000             // Số tiền
);

echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
if ($result['success'] && isset($result['data']['acc_no'])) {
    echo "Số VA: " . $result['data']['acc_no'] . "\n";
}
print_r($result);
```

```bash
php 05_create_va.php
```

---

## 🔷 API 6: Tra cứu giao dịch VA

```php
<?php
require_once __DIR__ . '/baokim-sdk/autoload.php';

use Baokim\B2B\BaokimAuth;
use Baokim\B2B\HostToHost\BaokimVA;

$auth = new BaokimAuth();
$token = $auth->getToken();
$vaService = new BaokimVA($token);

$result = $vaService->queryTransaction([
    'acc_no' => '00812345678901',   // Thay bằng số VA thật từ API 5
]);

echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
print_r($result);
```

```bash
php 06_query_va.php
```

---

## 🔷 API 7: Tạo đơn hàng Direct Connection

> ⚠️ Direct connection sử dụng credentials riêng (`direct_client_id`, `direct_client_secret`). Thêm vào config nếu có.

```php
<?php
require_once __DIR__ . '/baokim-sdk/autoload.php';

use Baokim\B2B\BaokimAuth;
use Baokim\B2B\Direct\BaokimDirect;

// Direct connection dùng credentials riêng
$directAuth = BaokimAuth::forDirectConnection();
$directToken = $directAuth->getToken();
$directService = new BaokimDirect($directToken);

$mrcOrderId = 'DRT_' . date('YmdHis') . '_' . rand(1000, 9999);

$result = $directService->createOrder([
    'mrc_order_id' => $mrcOrderId,
    'total_amount' => 150000,
    'description' => 'Thanh toan Direct ' . $mrcOrderId,
    'customer_info' => BaokimDirect::buildCustomerInfo(
        'Nguyen Van A', 'customer@email.com', '0901234567', '123 Nguyen Hue'
    ),
]);

echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
if ($result['success'] && isset($result['data']['payment_url'])) {
    echo "Payment URL: " . $result['data']['payment_url'] . "\n";
}
print_r($result);
```

```bash
php 07_direct_order.php
```

---

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
| `Config file not found` | Chưa tạo config.local.php | Copy từ config.php và điền thông tin |

---
© 2026 Baokim
