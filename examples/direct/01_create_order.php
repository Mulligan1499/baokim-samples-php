<?php
/**
 * Ví dụ 1: Tạo Đơn Hàng (Direct Connection)
 * 
 * Demo cách sử dụng BaokimDirect để tạo đơn hàng mới
 * Kết nối Direct sử dụng merchant_code (không dùng master/sub)
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

echo "=== Baokim B2B - Tạo Đơn Hàng (Direct Connection) ===\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $directService = new BaokimDirect($auth);
    
    // ============================================================
    // CHUẨN BỊ DỮ LIỆU ĐƠN HÀNG
    // ============================================================
    
    // Tạo mã đơn hàng duy nhất
    $mrcOrderId = 'DIRECT_' . date('YmdHis') . '_' . rand(1000, 9999);
    
    echo "1. Chuẩn bị dữ liệu đơn hàng\n";
    echo "   Mã đơn hàng: {$mrcOrderId}\n\n";
    
    // Tạo thông tin khách hàng
    $customerInfo = BaokimDirect::buildCustomerInfo(
        'Nguyen Van A',           // Tên
        'nguyenvana@email.com',   // Email
        '0901234567',             // Số điện thoại
        '313 Truong Trinh',       // Địa chỉ
        1,                        // Giới tính (1=male, 2=female)
        'KH001'                   // Mã khách hàng (optional)
    );
    
    // Tạo danh sách sản phẩm
    $items = [
        BaokimDirect::buildItem('SP001', 'Sản phẩm A', 100000, 2),
        BaokimDirect::buildItem('SP002', 'Sản phẩm B', 150000, 1),
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
    
    echo "2. Đang gọi API tạo đơn hàng (Direct)...\n";
    
    $result = $directService->createOrder([
        // === REQUIRED FIELDS ===
        'mrc_order_id' => $mrcOrderId,                    // required: Mã đơn hàng
        'total_amount' => $totalAmount,                   // required: Tổng tiền
        'description' => 'Thanh toan don hang ' . $mrcOrderId, // required: Mô tả
        
        // === OPTIONAL FIELDS (để null nếu không dùng) ===
        'payment_method' => BaokimDirect::PAYMENT_METHOD_VA, // optional: 1=VA, 2=BNPL, 3=Credit, 4=Installment, 5=ATM, 6=VNPayQR
        'items' => $items,                                // optional: Danh sách sản phẩm
        'customer_info' => $customerInfo,                 // optional: Thông tin khách hàng
        'url_success' => Config::get('url_success'),      // optional: URL redirect thành công
        'url_fail' => Config::get('url_fail'),            // optional: URL redirect thất bại
        'store_code' => null,                             // optional: Mã cửa hàng
        'branch_code' => null,                            // optional: Mã chi nhánh
        'staff_code' => null,                             // optional: Mã nhân viên
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
