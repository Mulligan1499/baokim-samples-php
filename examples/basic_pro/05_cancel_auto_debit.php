<?php
/**
 * Ví dụ 5: Hủy Thu Hộ Tự Động (Cancel Auto Debit)
 * 
 * Demo cách sử dụng BaokimOrder để hủy đăng ký thu hộ tự động
 * 
 * Lưu ý: Token là mã định danh thẻ/tài khoản thu hộ tự động
 * được trả về trong webhook khi khách hàng đăng ký thành công
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

// Token thu hộ tự động (nhận từ webhook khi đăng ký thành công)
$autoDebitToken = isset($argv[1]) ? $argv[1] : 'YOUR_AUTO_DEBIT_TOKEN';

echo "Token thu hộ tự động: {$autoDebitToken}\n";
echo "(Sử dụng: php 05_cancel_auto_debit.php YOUR_TOKEN)\n\n";

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
    
    $result = $orderService->cancelAutoDebit($autoDebitToken);
    
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
