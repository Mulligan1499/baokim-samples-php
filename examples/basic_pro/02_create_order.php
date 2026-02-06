<?php
/**
 * Ví dụ 2: Tạo Đơn Hàng (Basic Pro)
 * 
 * Demo cách sử dụng BaokimOrder để tạo đơn hàng mới
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

echo "=== Baokim B2B - Tạo Đơn Hàng ===\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $orderService = new BaokimOrder($auth);
    
    // ============================================================
    // CHUẨN BỊ DỮ LIỆU ĐƠN HÀNG
    // ============================================================
    
    // Tạo mã đơn hàng duy nhất
    $mrcOrderId = 'ORDER_' . date('YmdHis') . '_' . rand(1000, 9999);
    
    echo "1. Chuẩn bị dữ liệu đơn hàng\n";
    echo "   Mã đơn hàng: {$mrcOrderId}\n\n";
    
    // Tạo thông tin khách hàng
    $customerInfo = BaokimOrder::buildCustomerInfo(
        'Nguyen Van A',           // Tên
        'nguyenvana@email.com',   // Email
        '0901234567',             // Số điện thoại
        '313 Truong Trinh'     // Địa chỉ
    );
    
    // Tạo danh sách sản phẩm
    $items = [
        BaokimOrder::buildItem('SP001', 'Sản phẩm A', 100000, 2),
        BaokimOrder::buildItem('SP002', 'Sản phẩm B', 150000, 1),
    ];
    
    // Tính tổng tiền
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += $item['amount'] * $item['quantity'];
    }
    
    echo "   Tổng tiền: " . number_format($totalAmount) . " VND\n\n";
    
    // ============================================================
    // GỌI API TẠO ĐƠN HÀNG
    // ============================================================
    
    echo "2. Đang gọi API tạo đơn hàng...\n";
    
    $result = $orderService->createOrder([
        'mrc_order_id' => $mrcOrderId,
        'total_amount' => $totalAmount,
        'description' => 'Thanh toan don hang ' . $mrcOrderId,
        'customer_info' => $customerInfo,
        'url_success' => Config::get('url_success'),
        'url_fail' => Config::get('url_fail'),
    ]);
    
    // ============================================================
    // XỬ LÝ KẾT QUẢ
    // ============================================================
    
    echo "3. Kết quả:\n";
    echo "   Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "   Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    echo "   Message: " . $result['message'] . "\n\n";
    
    if ($result['success'] && $result['data']) {
        echo "=== Thông tin thanh toán ===\n";
        
        // URL thanh toán (nếu có)
        if (isset($result['data']['payment_url'])) {
            echo "Payment URL: " . $result['data']['payment_url'] . "\n";
        }
        
        if (isset($result['data']['redirect_url'])) {
            echo "Redirect URL: " . $result['data']['redirect_url'] . "\n";
        }
        
        // Thông tin VA (nếu là phương thức VA)
        if (isset($result['data']['acc_no'])) {
            echo "Số tài khoản VA: " . $result['data']['acc_no'] . "\n";
        }
        
        if (isset($result['data']['qr_string'])) {
            echo "QR String: " . substr($result['data']['qr_string'], 0, 50) . "...\n";
        }
        
        // Lưu response để debug
        echo "\n";
        echo "Full response data:\n";
        print_r($result['data']);
    } else {
        echo "!!! Đơn hàng không được tạo thành công !!!\n";
        echo "Vui lòng kiểm tra lại thông tin và thử lại.\n";
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
