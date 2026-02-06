<?php
/**
 * Ví dụ 3: Tra Cứu Đơn Hàng (Basic Pro)
 * 
 * Demo cách sử dụng BaokimOrder để tra cứu thông tin đơn hàng
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MasterSub\BaokimOrder;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Tra Cứu Đơn Hàng ===\n\n";

// Mã đơn hàng cần tra cứu (thay bằng mã đơn thực tế)
$mrcOrderId = isset($argv[1]) ? $argv[1] : 'ORDER_20240101120000_1234';

echo "Mã đơn hàng tra cứu: {$mrcOrderId}\n";
echo "(Truyền mã đơn qua command line: php 03_query_order.php YOUR_ORDER_ID)\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $orderService = new BaokimOrder($auth);
    
    // ============================================================
    // GỌI API TRA CỨU
    // ============================================================
    
    echo "Đang gọi API tra cứu...\n\n";
    
    $result = $orderService->queryOrder($mrcOrderId);
    
    // ============================================================
    // XỬ LÝ KẾT QUẢ
    // ============================================================
    
    echo "=== Kết quả ===\n";
    echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    echo "Message: " . $result['message'] . "\n\n";
    
    if ($result['success'] && $result['data']) {
        $order = $result['data'];
        
        echo "=== Thông tin đơn hàng ===\n";
        
        if (isset($order['order'])) {
            $orderInfo = $order['order'];
            echo "Mã đơn BK: " . (isset($orderInfo['id']) ? $orderInfo['id'] : 'N/A') . "\n";
            echo "Mã đơn MRC: " . (isset($orderInfo['mrc_order_id']) ? $orderInfo['mrc_order_id'] : 'N/A') . "\n";
            echo "Số tiền: " . (isset($orderInfo['total_amount']) ? number_format($orderInfo['total_amount']) : 'N/A') . " VND\n";
            echo "Trạng thái: " . (isset($orderInfo['stat']) ? $orderInfo['stat'] : 'N/A') . "\n";
            echo "Ngày tạo: " . (isset($orderInfo['created_at']) ? $orderInfo['created_at'] : 'N/A') . "\n";
        }
        
        if (isset($order['transactions']) && is_array($order['transactions'])) {
            echo "\n=== Danh sách giao dịch ===\n";
            foreach ($order['transactions'] as $idx => $trans) {
                echo "--- Giao dịch " . ($idx + 1) . " ---\n";
                echo "Mã GD: " . (isset($trans['id']) ? $trans['id'] : 'N/A') . "\n";
                echo "Số tiền: " . (isset($trans['amount']) ? number_format($trans['amount']) : 'N/A') . " VND\n";
                echo "Trạng thái: " . (isset($trans['stat']) ? $trans['stat'] : 'N/A') . "\n";
                echo "Thời gian: " . (isset($trans['completed_at']) ? $trans['completed_at'] : 'N/A') . "\n";
            }
        }
        
        echo "\nFull response:\n";
        print_r($result['data']);
    } else {
        echo "Không tìm thấy thông tin đơn hàng.\n";
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
}
