<?php
/**
 * Cấu hình kết nối Baokim B2B API
 * 
 * Hướng dẫn:
 * 1. Copy file .env.example thành .env tại thư mục gốc project
 * 2. Điền các thông tin được Baokim cung cấp vào file .env
 * 3. KHÔNG commit file .env lên git
 */

// Load phpdotenv
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->safeLoad();
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
    'base_url' => $_ENV['BAOKIM_BASE_URL'] ?? 'https://devtest.baokim.vn',
    
    /**
     * Timeout cho các request (giây)
     */
    'timeout' => (int)($_ENV['BAOKIM_TIMEOUT'] ?? 30),
    
    // ==================================================
    // THÔNG TIN XÁC THỰC MERCHANT
    // ==================================================
    
    /**
     * Mã Merchant được Baokim cung cấp
     */
    'merchant_code' => $_ENV['BAOKIM_MERCHANT_CODE'] ?? '',
    
    /**
     * Client ID cho OAuth2
     */
    'client_id' => $_ENV['BAOKIM_CLIENT_ID'] ?? '',
    
    /**
     * Client Secret cho OAuth2
     */
    'client_secret' => $_ENV['BAOKIM_CLIENT_SECRET'] ?? '',
    
    // ==================================================
    // THÔNG TIN MASTER/SUB MERCHANT (cho mô hình Master MRC)
    // ==================================================
    
    /**
     * Mã Master Merchant (nếu là mô hình Master MRC)
     */
    'master_merchant_code' => $_ENV['BAOKIM_MASTER_MERCHANT_CODE'] ?? '',
    
    /**
     * Mã Sub Merchant (nếu là mô hình Master MRC)
     */
    'sub_merchant_code' => $_ENV['BAOKIM_SUB_MERCHANT_CODE'] ?? '',

    // ==================================================
    // THÔNG TIN DIRECT CONNECTION (không qua Master Merchant)
    // ==================================================

    'direct_merchant_code' => $_ENV['BAOKIM_DIRECT_MERCHANT_CODE'] ?? '',
    'direct_client_id' => $_ENV['BAOKIM_DIRECT_CLIENT_ID'] ?? '',
    'direct_client_secret' => $_ENV['BAOKIM_DIRECT_CLIENT_SECRET'] ?? '',
    
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
    'url_success' => $_ENV['BAOKIM_URL_SUCCESS'] ?? 'https://your-domain.com/payment/success',
    
    /**
     * URL callback khi thanh toán thất bại
     */
    'url_fail' => $_ENV['BAOKIM_URL_FAIL'] ?? 'https://your-domain.com/payment/fail',
    
    /**
     * URL nhận webhook từ Baokim (gửi cho Baokim cấu hình)
     */
    'webhook_url' => $_ENV['BAOKIM_WEBHOOK_URL'] ?? 'https://your-domain.com/webhook/baokim',
];
