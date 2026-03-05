<?php
/**
 * Cấu hình kết nối Baokim B2B API
 * 
 * Hướng dẫn:
 * 1. Copy file này thành config.local.php
 * 2. Điền các thông tin được Baokim cung cấp
 * 3. KHÔNG commit file config.local.php lên git
 */

return [
    // ==================================================
    // CẤU HÌNH CHUNG
    // ==================================================
    
    /**
     * Base URL của Baokim API
     * - Dev/Test: https://devtest.baokim.vn
     * - Production: https://openapi.baokim.vn
     */
    'base_url' => 'https://devtest.baokim.vn',
    
    /**
     * Timeout cho các request (giây)
     */
    'timeout' => 30,
    
    // ==================================================
    // THÔNG TIN XÁC THỰC MERCHANT
    // ==================================================
    
    /**
     * Mã Merchant được Baokim cung cấp
     */
    'merchant_code' => 'b2bthiither127',
    
    /**
     * Client ID cho OAuth2
     */
    'client_id' => 'mmthiither22',
    
    /**
     * Client Secret cho OAuth2
     */
    'client_secret' => 'vpp6yTe4%BbnMdP7it2Dz0x3IOzrxUVu',
    
    // ==================================================
    // THÔNG TIN MASTER/SUB MERCHANT (cho mô hình Master MRC)
    // ==================================================
    
    /**
     * Mã Master Merchant (nếu là mô hình Master MRC)
     */
    'master_merchant_code' => 'mmthiither22',
    
    /**
     * Mã Sub Merchant (nếu là mô hình Master MRC)
     */
    'sub_merchant_code' => 'b2bthiither127',
    
    // ==================================================
    // THÔNG TIN DIRECT CONNECTION (không qua Master MRC)
    // ==================================================
    
    /**
     * Mã Merchant cho Direct connection
     */
    'direct_merchant_code' => 'b2bthiither127',
    
    /**
     * Client ID cho Direct connection OAuth2
     * (khác với client_id của Master)
     */
    'direct_client_id' => 'b2bthiither127',
    
    /**
     * Client Secret cho Direct connection OAuth2
     */
    'direct_client_secret' => 'vEy!xYnlpO41!2%XCXKHsjHhERLf2obZ',
    
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
    'url_success' => 'https://webhook.site/2f5fd254-547f-4238-a251-af771b61bf44',
    
    /**
     * URL callback khi thanh toán thất bại
     */
    'url_fail' => 'https://webhook.site/2f5fd254-547f-4238-a251-af771b61bf44',
    
    /**
     * URL nhận webhook từ Baokim (gửi cho Baokim cấu hình)
     */
    'webhook_url' => 'https://webhook.site/2f5fd254-547f-4238-a251-af771b61bf44',
];
