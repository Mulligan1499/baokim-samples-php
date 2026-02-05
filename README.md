# Baokim B2B API - PHP 7 Example

Bộ source code mẫu tích hợp Baokim B2B API, viết bằng PHP thuần (PHP 7.x), không dependencies.

## 🔧 Yêu cầu
- PHP 7.0+
- Extensions: `curl`, `openssl`, `json`

---

## 📦 Bước 1: Cài đặt

```bash
git clone https://github.com/Mulligan1499/baokim-b2b-php-example.git
cd baokim-b2b-php-example
```

---

## ⚙️ Bước 2: Cấu hình

### 2.1. Tạo file config

```bash
cp config/config.php config/config.local.php
```

### 2.2. Điền thông tin vào `config/config.local.php`

```php
return [
    // Base URL (nhận từ Baokim)
    'base_url' => 'https://devtest.baokim.vn',     // Dev/Test
    // 'base_url' => 'https://openapi.baokim.vn', // Production
    
    // Thông tin xác thực (nhận từ Baokim)
    'merchant_code' => 'YOUR_MERCHANT_CODE',
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    
    // Master/Sub Merchant (cho mô hình Master MRC)
    'master_merchant_code' => 'YOUR_MASTER_MERCHANT_CODE',
    'sub_merchant_code' => 'YOUR_SUB_MERCHANT_CODE',
    
    // Callback URLs (thay bằng domain thực của MRC)
    'url_success' => 'https://your-domain.com/payment/success',
    'url_fail' => 'https://your-domain.com/payment/fail',
    'webhook_url' => 'https://your-domain.com/webhook/baokim',
    
    // RSA Keys
    'merchant_private_key_path' => __DIR__ . '/../keys/merchant_private.pem',
    'baokim_public_key_path' => __DIR__ . '/../keys/baokim_public.pem',
];
```

### 2.3. Tạo thư mục keys và đặt RSA Keys

```bash
# Copy private key của MRC vào đây (dùng để ký request)
keys/merchant_private.pem

# Copy public key của Baokim vào đây (dùng để verify webhook)
keys/baokim_public.pem
```

> **⚠️ Lưu ý:** 
> - Private key do MRC tự generate, public key tương ứng gửi cho Baokim
> - Public key của Baokim sẽ được Baokim cung cấp khi đăng ký tích hợp

---

## 🚀 Bước 3: Sử dụng

### 📁 Cấu trúc thư mục

```
├── config/config.local.php     # File config (tạo từ config.php)
├── src/
│   ├── autoload.php            # Tự động load các class
│   ├── BaokimAuth.php          # Xác thực OAuth2, lấy token
│   ├── BaokimOrder.php         # API đơn hàng (Basic Pro)
│   ├── BaokimVA.php            # API Virtual Account (Host to Host)
│   ├── WebhookHandler.php      # Xử lý webhook từ Baokim
│   └── ...
├── examples/                   # Code mẫu chạy sẵn
└── keys/                       # RSA Keys
```

---

## 📖 Hướng dẫn sử dụng từng Class

### 1️⃣ BaokimAuth - Xác thực & Lấy Token

```php
<?php
require_once 'src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;

// Load config
Config::load();

// Khởi tạo Auth
$auth = new BaokimAuth();

// Lấy access token
$token = $auth->getToken();
echo "Token: " . $token;

// Kiểm tra token còn hạn không
if ($auth->isTokenValid()) {
    echo "Token còn hiệu lực";
}
```

---

### 2️⃣ BaokimOrder - API Đơn hàng (Basic Pro)

**Các functions có sẵn:**
| Function | Mô tả |
|----------|-------|
| `createOrder($orderData)` | Tạo đơn hàng mới |
| `queryOrder($mrcOrderId)` | Tra cứu đơn hàng |
| `refundOrder($mrcOrderId, $amount, $description)` | Hoàn tiền đơn hàng |
| `cancelAutoDebit($token)` | Hủy thu hộ tự động |
| `buildCustomerInfo(...)` | Helper tạo thông tin khách hàng |
| `buildItem(...)` | Helper tạo item sản phẩm |

#### Ví dụ: Tạo đơn hàng

```php
<?php
require_once 'src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\BaokimOrder;

// Load config
Config::load();

// Khởi tạo
$auth = new BaokimAuth();
$orderService = new BaokimOrder($auth);

// Tạo thông tin khách hàng
$customerInfo = BaokimOrder::buildCustomerInfo(
    'Nguyen Van A',           // Tên
    'nguyenvana@email.com',   // Email
    '0901234567',             // SĐT
    '123 ABC Street'          // Địa chỉ (optional)
);

// Tạo đơn hàng
$result = $orderService->createOrder([
    'mrc_order_id' => 'ORDER_' . time(),  // Mã đơn hàng của MRC (unique)
    'total_amount' => 100000,              // Tổng tiền (VND)
    'description' => 'Thanh toan don hang',
    'customer_info' => $customerInfo,
    'url_success' => 'https://your-domain.com/success',
    'url_fail' => 'https://your-domain.com/fail',
]);

// Xử lý kết quả
if ($result['success']) {
    echo "Payment URL: " . $result['data']['payment_url'];
} else {
    echo "Error: " . $result['message'];
}
```

#### Ví dụ: Tra cứu đơn hàng

```php
$result = $orderService->queryOrder('ORDER_123456');

if ($result['success']) {
    print_r($result['data']); // Thông tin đơn hàng
}
```

#### Ví dụ: Hoàn tiền

```php
$result = $orderService->refundOrder(
    'ORDER_123456',        // Mã đơn hàng
    50000,                 // Số tiền hoàn (0 = hoàn toàn bộ)
    'Khach yeu cau hoan'   // Lý do
);
```

---

### 3️⃣ BaokimVA - API Virtual Account (Host to Host)

**Các functions có sẵn:**
| Function | Mô tả |
|----------|-------|
| `createVA($vaData)` | Tạo VA mới |
| `updateVA($accNo, $updateData)` | Cập nhật VA |
| `queryTransaction($queryData)` | Tra cứu giao dịch |
| `createDynamicVA(...)` | Shortcut tạo Dynamic VA |
| `createStaticVA(...)` | Shortcut tạo Static VA |

#### Ví dụ: Tạo Dynamic VA

```php
<?php
require_once 'src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\BaokimVA;

// Load config
Config::load();

// Khởi tạo
$auth = new BaokimAuth();
$vaService = new BaokimVA($auth);

// Tạo Dynamic VA (thu theo số tiền cố định)
$result = $vaService->createDynamicVA(
    'NGUYEN VAN A',           // Tên chủ VA
    'ORDER_' . time(),        // Mã đơn hàng
    500000,                   // Số tiền cần thu
    'Thanh toan don hang'     // Mô tả (optional)
);

if ($result['success']) {
    echo "Số VA: " . $result['data']['acc_no'];
    echo "QR: " . $result['data']['qr_path'];
}
```

#### Ví dụ: Tạo Static VA

```php
// Tạo Static VA (thu nhiều lần, có thời hạn)
$result = $vaService->createStaticVA(
    'NGUYEN VAN A',           // Tên chủ VA
    'CUSTOMER_001',           // Mã định danh KH
    '2024-12-31 23:59:59',    // Ngày hết hạn
    10000,                    // Số tiền tối thiểu (optional)
    10000000                  // Số tiền tối đa (optional)
);
```

#### Ví dụ: Tra cứu giao dịch VA

```php
$result = $vaService->queryTransaction([
    'acc_no' => '123456789',           // Số VA
    // hoặc
    'mrc_order_id' => 'ORDER_123456',  // Mã đơn hàng
    'from_date' => '2024-01-01',       // Từ ngày (optional)
    'to_date' => '2024-01-31',         // Đến ngày (optional)
]);
```

---

### 4️⃣ WebhookHandler - Xử lý Webhook từ Baokim

#### Ví dụ: File nhận webhook

```php
<?php
// File: webhook_receiver.php (đặt ở URL cho Baokim gọi)

require_once 'src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\WebhookHandler;

Config::load();

// Khởi tạo handler
$webhook = new WebhookHandler();

// Đăng ký xử lý khi thanh toán thành công
$webhook->onPayment(function($paymentData, $fullPayload) {
    // $paymentData chứa: mrc_order_id, amount, stat, bpm_txn_id, etc.
    
    $orderId = $paymentData['mrc_order_id'];
    $amount = $paymentData['amount'];
    $status = $paymentData['stat'];  // 'c' = completed
    
    // Cập nhật database của MRC
    // updateOrderStatus($orderId, $status);
    
    // Log
    error_log("Payment received: Order={$orderId}, Amount={$amount}");
    
    // Return null để dùng response mặc định
    return null;
});

// Đăng ký xử lý khi hoàn tiền
$webhook->onRefund(function($refundData, $fullPayload) {
    $orderId = $refundData['mrc_order_id'];
    // Xử lý hoàn tiền...
    return null;
});

// Xử lý webhook (tự verify signature)
$response = $webhook->handle(true);

// Trả response cho Baokim
$webhook->sendResponse($response);
```

---

## 🧪 Chạy Test

```bash
# Test tất cả APIs
php test_full_flow.php

# Test từng API riêng
php examples/basic_pro/01_get_token.php
php examples/basic_pro/02_create_order.php
php examples/basic_pro/03_query_order.php
php examples/va_host_to_host/05_create_va.php
```

---

## 📚 Tham khảo API Endpoints

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

---

## ❓ Checklist trước khi tích hợp Production

- [ ] Đã có đầy đủ thông tin từ Baokim: `merchant_code`, `client_id`, `client_secret`
- [ ] Đã generate RSA key pair và gửi public key cho Baokim
- [ ] Đã nhận public key của Baokim để verify webhook
- [ ] Đã cấu hình webhook URL cho Baokim
- [ ] Đã test thành công trên môi trường Dev/Test
- [ ] Đã chuyển `base_url` sang `https://openapi.baokim.vn`

---

## 🖥️ Replit

Import repo → Tạo `config/config.local.php` → Tạo `keys/merchant_private.pem` → Run

---

© 2026 Baokim
