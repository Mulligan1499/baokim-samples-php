<?php
/**
 * Cấu hình kết nối Baokim B2B API
 * 
 * Hướng dẫn:
 * 1. Copy file .env.example thành .env tại thư mục gốc project
 * 2. Điền các thông tin được Baokim cung cấp vào file .env
 * 3. KHÔNG commit file .env lên git
 */

// Load biến môi trường từ file .env (không dùng thư viện bên ngoài)
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Bỏ qua comment
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

return [
    // ==================================================
    // CẤU HÌNH CHUNG
    // ==================================================
    
    /**
     * Base URL của Baokim API
     * - Dev/Test: https://devtest.baokim.vn
     * - Production: https://openapi.baokim.vn
     */
    'base_url' => getenv('BAOKIM_BASE_URL') ?: 'https://devtest.baokim.vn',
    
    /**
     * Timeout cho các request (giây)
     */
    'timeout' => (int)(getenv('BAOKIM_TIMEOUT') ?: 30),
    
    // ==================================================
    // THÔNG TIN XÁC THỰC MERCHANT
    // ==================================================
    
    /**
     * Mã Merchant được Baokim cung cấp
     */
    'merchant_code' => getenv('BAOKIM_MERCHANT_CODE') ?: '',
    
    /**
     * Client ID cho OAuth2
     */
    'client_id' => getenv('BAOKIM_CLIENT_ID') ?: '',
    
    /**
     * Client Secret cho OAuth2
     */
    'client_secret' => getenv('BAOKIM_CLIENT_SECRET') ?: '',
    
    // ==================================================
    // THÔNG TIN MASTER/SUB MERCHANT (cho mô hình Master MRC)
    // ==================================================
    
    /**
     * Mã Master Merchant (nếu là mô hình Master MRC)
     */
    'master_merchant_code' => getenv('BAOKIM_MASTER_MERCHANT_CODE') ?: '',
    
    /**
     * Mã Sub Merchant (nếu là mô hình Master MRC)
     */
    'sub_merchant_code' => getenv('BAOKIM_SUB_MERCHANT_CODE') ?: '',

    // ==================================================
    // THÔNG TIN DIRECT CONNECTION (không qua Master Merchant)
    // ==================================================

    'direct_merchant_code' => getenv('BAOKIM_DIRECT_MERCHANT_CODE') ?: '',
    'direct_client_id' => getenv('BAOKIM_DIRECT_CLIENT_ID') ?: '',
    'direct_client_secret' => getenv('BAOKIM_DIRECT_CLIENT_SECRET') ?: '',
    
    // ==================================================
    // CẤU HÌNH CHỮ KÝ SỐ (RSA)
    // ==================================================
    
    /**
     * Đường dẫn tới Private Key của Merchant
     * - Dùng để ký các request gửi tới Baokim
     * - Định dạng PEM
     */
    'merchant_private_key_path' => __DIR__ . '/../keys/merchant_private.pem',
    
    /**
     * Đường dẫn tới Public Key của Baokim
     * - Dùng để verify signature trong webhook từ Baokim
     * - Được Baokim cung cấp khi đăng ký tích hợp
     */
    'baokim_public_key_path' => __DIR__ . '/../keys/baokim_public.pem',
    
    // ==================================================
    // CẤU HÌNH WEBHOOK
    // ==================================================
    
    /**
     * URL callback khi thanh toán thành công
     */
    'url_success' => getenv('BAOKIM_URL_SUCCESS') ?: 'https://your-domain.com/payment/success',
    
    /**
     * URL callback khi thanh toán thất bại
     */
    'url_fail' => getenv('BAOKIM_URL_FAIL') ?: 'https://your-domain.com/payment/fail',
    
    /**
     * URL nhận webhook từ Baokim (gửi cho Baokim cấu hình)
     */
    'webhook_url' => getenv('BAOKIM_WEBHOOK_URL') ?: 'https://your-domain.com/webhook/baokim',
];
