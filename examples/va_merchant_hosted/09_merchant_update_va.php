<?php
/**
 * Ví dụ 9: Cập nhật Virtual Account - Merchant Hosted (Direct Connection)
 * 
 * Demo cách sử dụng BaokimMerchantVA để cập nhật VA
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MerchantHosted\BaokimMerchantVA;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Cập nhật VA (Merchant Hosted) ===\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $directAuth = BaokimAuth::forDirectConnection();
    $vaService = new BaokimMerchantVA($directAuth->getToken());
    
    // ============================================================
    // CẬP NHẬT VA
    // ============================================================
    
    // Mã đơn hàng cần cập nhật (thay bằng mrc_order_id thật từ API tạo VA)
    $mrcOrderId = $argv[1] ?? 'MH_VA_20260314120000_1234';
    
    echo "Mã đơn hàng: {$mrcOrderId}\n\n";
    
    // --- Ví dụ 1: Cập nhật ngày hết hạn ---
    echo "=== Cập nhật ngày hết hạn ===\n";
    
    $newExpireDate = date('Y-m-d H:i:s', strtotime('+60 days'));
    echo "Expire date mới: {$newExpireDate}\n";
    
    $result = $vaService->updateVA($mrcOrderId, [
        'expire_date' => $newExpireDate,
    ]);
    
    echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    
    if ($result['success']) {
        echo "✅ Cập nhật thành công!\n";
    } else {
        echo "❌ Lỗi: " . $result['message'] . "\n";
    }
    
    // --- Ví dụ 2: Cập nhật số tiền ---
    echo "\n=== Cập nhật số tiền thu ===\n";
    
    $result2 = $vaService->updateVA($mrcOrderId, [
        'collect_amount_min' => 50000,
        'collect_amount_max' => 50000000,
    ]);
    
    echo "Success: " . ($result2['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result2['code'] . " - " . ErrorCode::getMessage($result2['code']) . "\n";
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
