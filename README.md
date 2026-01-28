# Baokim B2B API - PHP7 Example Code

Bộ source code mẫu để tích hợp với hệ thống B2B của Baokim, viết bằng PHP thuần (PHP 7.x), không sử dụng thư viện bên ngoài.

## 📋 Mục lục

- [Yêu cầu hệ thống](#-yêu-cầu-hệ-thống)
- [Cài đặt](#-cài-đặt)
- [Cấu hình](#-cấu-hình)
- [Cấu trúc thư mục](#-cấu-trúc-thư-mục)
- [Hướng dẫn sử dụng](#-hướng-dẫn-sử-dụng)
- [API Reference](#-api-reference)
- [Mã lỗi](#-mã-lỗi)
- [Troubleshooting](#-troubleshooting)

## 🔧 Yêu cầu hệ thống

- **PHP**: 7.0 trở lên
- **Extensions bắt buộc**:
  - `curl` - Gọi HTTP requests
  - `openssl` - Ký số RSA SHA256
  - `json` - Parse JSON

### Kiểm tra extensions

```bash
php -m | grep -E "curl|openssl|json"
```

## 📦 Cài đặt

1. **Clone hoặc download** source code về thư mục dự án:

```bash
git clone <repository-url> baokim-b2b
cd baokim-b2b
```

2. **Tạo file cấu hình**:

```bash
cp config/config.php config/config.local.php
```

3. **Tạo RSA key pair** (nếu chưa có):

```bash
# Tạo thư mục keys
mkdir -p keys

# Tạo private key (2048 bits)
openssl genrsa -out keys/merchant_private.pem 2048

# Tạo public key từ private key
openssl rsa -in keys/merchant_private.pem -pubout -out keys/merchant_public.pem
```

4. **Gửi public key cho Baokim** để đăng ký và nhận lại:
   - `baokim_public.pem` - Public key của Baokim (để verify webhook)
   - Thông tin credentials: `merchant_code`, `client_id`, `client_secret`

## ⚙️ Cấu hình

Chỉnh sửa file `config/config.local.php`:

```php
return [
    // Base URL
    'base_url' => 'https://sandbox.baokim.vn',  // hoặc https://openapi.baokim.vn cho production
    
    // Thông tin xác thực (Baokim cung cấp)
    'merchant_code' => 'YOUR_MERCHANT_CODE',
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    
    // Mã merchant (cho mô hình Master MRC)
    'master_merchant_code' => 'YOUR_MASTER_MERCHANT_CODE',
    'sub_merchant_code' => 'YOUR_SUB_MERCHANT_CODE',
    
    // Đường dẫn RSA keys
    'merchant_private_key_path' => __DIR__ . '/../keys/merchant_private.pem',
    'baokim_public_key_path' => __DIR__ . '/../keys/baokim_public.pem',
    
    // URLs callback
    'url_success' => 'https://your-domain.com/payment/success',
    'url_fail' => 'https://your-domain.com/payment/fail',
];
```

## 📁 Cấu trúc thư mục

```
php7-b2b-example/
├── config/
│   ├── config.php              # File config mẫu
│   └── config.local.php        # File config thực (không commit)
├── src/
│   ├── autoload.php            # PSR-4 Autoloader
│   ├── Config.php              # Quản lý cấu hình
│   ├── SignatureHelper.php     # Ký số RSA SHA256
│   ├── HttpClient.php          # HTTP Client (cURL thuần)
│   ├── BaokimAuth.php          # Xác thực OAuth2
│   ├── BaokimOrder.php         # API đơn hàng (Basic Pro)
│   ├── BaokimVA.php            # API Virtual Account (H2H)
│   ├── WebhookHandler.php      # Xử lý webhook
│   └── ErrorCode.php           # Mapping mã lỗi
├── keys/
│   ├── merchant_private.pem    # Private key của Merchant
│   └── baokim_public.pem       # Public key của Baokim
├── examples/
│   ├── 01_get_token.php        # Ví dụ lấy token
│   ├── 02_create_order.php     # Ví dụ tạo đơn hàng
│   ├── 03_query_order.php      # Ví dụ tra cứu đơn hàng
│   ├── 04_refund_order.php     # Ví dụ hoàn tiền
│   ├── 05_create_va.php        # Ví dụ tạo VA
│   ├── 06_update_va.php        # Ví dụ cập nhật VA
│   ├── 07_query_transaction.php # Ví dụ tra cứu giao dịch VA
│   └── webhook_receiver.php    # Endpoint nhận webhook
├── logs/                       # Thư mục log (tự tạo)
└── README.md
```

## 🚀 Hướng dẫn sử dụng

### 1. Lấy Access Token

```php
<?php
require_once 'src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;

Config::load();

$auth = new BaokimAuth();
$token = $auth->getToken();

echo "Token: " . $token;
```

### 2. Tạo đơn hàng (Basic Pro)

```php
<?php
require_once 'src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\BaokimOrder;

Config::load();

$auth = new BaokimAuth();
$orderService = new BaokimOrder($auth);

$result = $orderService->createOrder([
    'mrc_order_id' => 'ORDER_' . time(),
    'total_amount' => 100000,
    'description' => 'Thanh toán đơn hàng',
    'payment_method' => BaokimOrder::PAYMENT_METHOD_VA,
    'customer_info' => [
        'name' => 'Nguyen Van A',
        'email' => 'test@email.com',
        'phone' => '0901234567',
    ],
]);

if ($result['success']) {
    echo "Payment URL: " . $result['data']['payment_url'];
}
```

### 3. Tạo Virtual Account (VA H2H)

```php
<?php
require_once 'src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\BaokimVA;

Config::load();

$auth = new BaokimAuth();
$vaService = new BaokimVA($auth);

// Tạo Dynamic VA (cho từng đơn hàng)
$result = $vaService->createDynamicVA(
    'NGUYEN VAN A',          // Tên chủ tài khoản
    'ORDER_123',              // Mã đơn hàng
    500000,                   // Số tiền cần thu
    'Thanh toan don hang'
);

if ($result['success']) {
    echo "Số VA: " . $result['data']['acc_no'];
    echo "Ngân hàng: " . $result['data']['bank_name'];
}
```

### 4. Xử lý Webhook

Tạo endpoint nhận webhook từ Baokim:

```php
<?php
// webhook.php - Deploy lên server
require_once 'src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\WebhookHandler;

Config::load();

$handler = new WebhookHandler();

$handler->onPayment(function($data, $payload) {
    // Xử lý thanh toán thành công
    $orderId = $data['transaction']['mrc_order_id'] ?? null;
    $amount = $data['transaction']['amount'] ?? 0;
    
    // TODO: Cập nhật database
    // updateOrderStatus($orderId, 'PAID');
    
    return null; // Dùng default response
});

$response = $handler->handle(true); // true = verify signature
$handler->sendResponse($response);
```

### 5. Chạy ví dụ

```bash
# Lấy token
php examples/01_get_token.php

# Tạo đơn hàng
php examples/02_create_order.php

# Tra cứu đơn hàng
php examples/03_query_order.php YOUR_ORDER_ID

# Tạo VA
php examples/05_create_va.php
```

## 📚 API Reference

### BaokimAuth

| Method | Mô tả |
|--------|-------|
| `getToken($forceRefresh)` | Lấy access token (tự động cache) |
| `isTokenValid()` | Kiểm tra token còn hiệu lực |
| `getAuthorizationHeader()` | Lấy header "Bearer {token}" |

### BaokimOrder (Basic Pro)

| Method | Mô tả |
|--------|-------|
| `createOrder($data)` | Tạo đơn hàng mới |
| `queryOrder($mrcOrderId)` | Tra cứu thông tin đơn hàng |
| `refundOrder($mrcOrderId, $amount, $desc)` | Hoàn tiền đơn hàng |
| `cancelAutoDebit($mrcOrderId)` | Hủy thu hộ tự động |

### BaokimVA (VA Host to Host)

| Method | Mô tả |
|--------|-------|
| `createVA($data)` | Tạo VA mới (đầy đủ tham số) |
| `createDynamicVA(...)` | Tạo Dynamic VA nhanh |
| `createStaticVA(...)` | Tạo Static VA nhanh |
| `updateVA($accNo, $data)` | Cập nhật thông tin VA |
| `queryTransaction($query)` | Tra cứu giao dịch |

### SignatureHelper

| Method | Mô tả |
|--------|-------|
| `sign($data, $keyPath)` | Ký dữ liệu bằng private key |
| `verify($data, $sig, $keyPath)` | Xác thực chữ ký |
| `generateKeyPair($bits)` | Tạo cặp RSA key mới |

## ❌ Mã lỗi

| Code | Mô tả |
|------|-------|
| `0` | Thành công |
| `100` | Đang xử lý |
| `101` | Thành công - Cần redirect trình duyệt |
| `102` | Lỗi từ nhà cung cấp dịch vụ |
| `104` | Chữ ký không hợp lệ |
| `111` | Xác thực thất bại |
| `422` | Dữ liệu không hợp lệ |
| `707` | Mã đơn hàng đã tồn tại |

## 🔍 Troubleshooting

### 1. Lỗi "Signature invalid"

- Kiểm tra private key có đúng format PEM không
- Đảm bảo public key đã được đăng ký với Baokim
- Kiểm tra request body không bị thay đổi sau khi ký

### 2. Lỗi "Authentication failed"

- Kiểm tra `merchant_code`, `client_id`, `client_secret`
- Đảm bảo thông tin khớp với môi trường (sandbox/production)

### 3. Lỗi cURL

```bash
# Kiểm tra PHP có extension curl
php -m | grep curl

# Cài đặt nếu chưa có (Ubuntu)
sudo apt-get install php-curl
```

### 4. Webhook không nhận được

- Đảm bảo URL webhook public và accessible
- Kiểm tra firewall/security không block Baokim
- Verify SSL certificate hợp lệ

## 📞 Liên hệ hỗ trợ

- **Email**: support@baokim.vn
- **Hotline**: 1900-xxxx
- **Tài liệu API**: https://openapi.baokim.vn/b2b-api-mastermrc

---

© 2024 Baokim. All rights reserved.
