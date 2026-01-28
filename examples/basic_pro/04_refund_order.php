<?php
/**
 * Ví dụ 4: Hoàn Tiền Đơn Hàng (Basic Pro)
 * 
 * Demo cách sử dụng BaokimOrder để hoàn tiền cho đơn hàng
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

echo "=== Baokim B2B - Hoàn Tiền Đơn Hàng ===\n\n";

// Thông tin hoàn tiền (thay bằng dữ liệu thực tế)
$mrcOrderId = isset($argv[1]) ? $argv[1] : 'ORDER_20240101120000_1234';
$refundAmount = isset($argv[2]) ? (int)$argv[2] : 0; // 0 = hoàn toàn bộ
$refundReason = isset($argv[3]) ? $argv[3] : 'Hoàn tiền theo yêu cầu khách hàng';

echo "Mã đơn hàng: {$mrcOrderId}\n";
echo "Số tiền hoàn: " . ($refundAmount > 0 ? number_format($refundAmount) . " VND" : "Toàn bộ") . "\n";
echo "Lý do: {$refundReason}\n\n";

echo "(Sử dụng: php 04_refund_order.php ORDER_ID [AMOUNT] [REASON])\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $orderService = new BaokimOrder($auth);
    
    // ============================================================
    // GỌI API HOÀN TIỀN
    // ============================================================
    
    echo "Đang gọi API hoàn tiền...\n\n";
    
    $result = $orderService->refundOrder($mrcOrderId, $refundAmount, $refundReason);
    
    // ============================================================
    // XỬ LÝ KẾT QUẢ
    // ============================================================
    
    echo "=== Kết quả ===\n";
    echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    echo "Message: " . $result['message'] . "\n\n";
    
    if ($result['success']) {
        echo "✓ Hoàn tiền thành công!\n";
        
        if ($result['data']) {
            echo "\nChi tiết:\n";
            print_r($result['data']);
        }
    } else {
        echo "✗ Hoàn tiền thất bại!\n";
        echo "Vui lòng kiểm tra lại mã đơn hàng và trạng thái đơn.\n";
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
}
