<?php
/**
 * Ví dụ 1: Lấy Access Token
 * 
 * Demo cách sử dụng BaokimAuth để lấy token xác thực
 * 
 * @package Baokim\B2B\Examples
 */

// Load autoloader
require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Lấy Access Token ===\n\n";

try {
    // Load config (tự động tìm config.local.php hoặc config.php)
    Config::load();
    
    echo "1. Config đã load từ: " . Config::get('base_url') . "\n";
    echo "   Merchant Code: " . Config::get('merchant_code') . "\n\n";
    
    // Khởi tạo Auth
    $auth = new BaokimAuth();
    
    echo "2. Đang gọi API lấy token...\n";
    
    // Lấy token
    $token = $auth->getToken();
    
    echo "3. Lấy token thành công!\n\n";
    
    // Hiển thị thông tin token
    $tokenInfo = $auth->getTokenInfo();
    echo "=== Thông tin Token ===\n";
    echo "Access Token: " . substr($token, 0, 50) . "...\n";
    echo "Hết hạn lúc: " . date('Y-m-d H:i:s', $tokenInfo['expired_at']) . "\n";
    echo "Còn hiệu lực: " . $tokenInfo['expires_in'] . " giây\n";
    echo "Authorization header: " . $auth->getAuthorizationHeader() . "\n\n";
    
    // Thử lấy lại token (sẽ dùng cache nếu còn hiệu lực)
    echo "4. Thử lấy token lần 2 (sẽ dùng cache)...\n";
    $token2 = $auth->getToken();
    
    if ($token === $token2) {
        echo "   => Token được lấy từ cache (không gọi lại API)\n";
    } else {
        echo "   => Token mới được tạo\n";
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Kiểm tra mã lỗi nếu có
    $code = $e->getCode();
    if ($code > 0) {
        echo "Error Code: " . $code . " - " . ErrorCode::getMessage($code) . "\n";
    }
}
