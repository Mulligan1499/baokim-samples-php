<?php
/**
 * Ví dụ 5: Hủy Thu Hộ Tự Động (Basic Pro)
 * 
 * Demo cách sử dụng BaokimOrder để hủy token thẻ lưu
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\BaokimOrder;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Hủy Thu Hộ Tự Động ===\n\n";

// Thông tin hủy (thay bằng dữ liệu thực tế)
$token = isset($argv[1]) ? $argv[1] : 'TOKEN_STRING_FROM_BAOKIM';
$resultCode = isset($argv[2]) ? (int)$argv[2] : 0;
$resultMessage = isset($argv[3]) ? $argv[3] : 'Huy thu ho tu dong theo yeu cau';
$tokenStatus = 0; // 0=Deactive, 1=Active

echo "Token: {$token}\n";
echo "Result Code: {$resultCode}\n";
echo "Result Message: {$resultMessage}\n";
echo "Token Status: " . ($tokenStatus == 1 ? 'Active' : 'Deactive') . "\n\n";

echo "(Sử dụng: php 05_cancel_auto_debit.php TOKEN [RESULT_CODE] [RESULT_MESSAGE])\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $orderService = new BaokimOrder($auth);
    
    // ============================================================
    // GỌI API HỦY THU HỘ TỰ ĐỘNG
    // ============================================================
    
    echo "Đang gọi API hủy thu hộ tự động...\n\n";
    
    $result = $orderService->cancelAutoDebit($resultCode, $resultMessage, $token, $tokenStatus);
    
    // ============================================================
    // XỬ LÝ KẾT QUẢ
    // ============================================================
    
    echo "=== Kết quả ===\n";
    echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    echo "Message: " . $result['message'] . "\n\n";
    
    if ($result['success']) {
        echo "✓ Hủy thu hộ tự động thành công!\n";
        
        if ($result['data']) {
            echo "\nChi tiết:\n";
            print_r($result['data']);
        }
    } else {
        echo "✗ Hủy thu hộ tự động thất bại!\n";
        echo "Vui lòng kiểm tra lại token và thử lại.\n";
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
}
