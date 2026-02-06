<?php
/**
 * Ví dụ 4: Hủy Đơn Hàng (Direct Connection)
 * 
 * Demo cách sử dụng BaokimDirect để hủy đơn hàng chưa thanh toán
 * 
 * Lưu ý: Chỉ có thể hủy đơn hàng chưa được thanh toán
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\Direct\BaokimDirect;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Hủy Đơn Hàng (Direct Connection) ===\n\n";

// Mã đơn hàng cần hủy (thay bằng mã đơn thực tế)
$mrcOrderId = isset($argv[1]) ? $argv[1] : 'DIRECT_20240101120000_1234';

echo "Mã đơn hàng cần hủy: {$mrcOrderId}\n";
echo "(Sử dụng: php 04_cancel_order.php ORDER_ID)\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $directService = new BaokimDirect($auth);
    
    // ============================================================
    // GỌI API HỦY ĐƠN HÀNG
    // ============================================================
    
    echo "Đang gọi API hủy đơn hàng...\n\n";
    
    $result = $directService->cancelOrder($mrcOrderId);
    
    // ============================================================
    // XỬ LÝ KẾT QUẢ
    // ============================================================
    
    echo "=== Kết quả ===\n";
    echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    echo "Message: " . $result['message'] . "\n\n";
    
    if ($result['success']) {
        echo "✓ Hủy đơn hàng thành công!\n";
        
        if ($result['data']) {
            echo "\nChi tiết:\n";
            print_r($result['data']);
        }
    } else {
        echo "✗ Hủy đơn hàng thất bại!\n";
        echo "Lưu ý: Chỉ có thể hủy đơn hàng chưa được thanh toán.\n";
        echo "Vui lòng kiểm tra lại mã đơn hàng và trạng thái đơn.\n";
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
}
