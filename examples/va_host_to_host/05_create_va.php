<?php
/**
 * Ví dụ 5: Tạo Virtual Account (VA Host to Host)
 * 
 * Demo cách sử dụng BaokimVA để tạo VA mới
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\BaokimVA;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Tạo Virtual Account ===\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $vaService = new BaokimVA($auth);
    
    // ============================================================
    // VÍ DỤ 1: TẠO DYNAMIC VA (cho từng đơn hàng)
    // ============================================================
    
    echo "=== Ví dụ 1: Tạo Dynamic VA ===\n\n";
    
    $mrcOrderId = 'VA_ORDER_' . date('YmdHis') . '_' . rand(1000, 9999);
    $amount = 500000; // 500,000 VND
    
    echo "Mã đơn hàng: {$mrcOrderId}\n";
    echo "Số tiền cần thu: " . number_format($amount) . " VND\n\n";
    
    echo "Đang gọi API tạo Dynamic VA...\n";
    
    $result1 = $vaService->createDynamicVA(
        'NGUYEN VAN A',           // Tên chủ tài khoản
        $mrcOrderId,               // Mã đơn hàng
        $amount,                   // Số tiền
        'Thanh toan don hang ' . $mrcOrderId
    );
    
    echo "\nKết quả:\n";
    echo "Success: " . ($result1['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result1['code'] . " - " . ErrorCode::getMessage($result1['code']) . "\n";
    
    if ($result1['success'] && $result1['data']) {
        $va = $result1['data'];
        echo "\n=== Thông tin VA ===\n";
        echo "Số VA: " . (isset($va['acc_no']) ? $va['acc_no'] : 'N/A') . "\n";
        echo "Tên ngân hàng: " . (isset($va['bank_name']) ? $va['bank_name'] : 'N/A') . "\n";
        echo "QR String: " . (isset($va['qr_string']) ? substr($va['qr_string'], 0, 50) . '...' : 'N/A') . "\n";
        echo "QR Image URL: " . (isset($va['qr_path']) ? $va['qr_path'] : 'N/A') . "\n";
        
        echo "\n=> Khách hàng chuyển khoản vào số VA trên để thanh toán.\n";
    }
    
    // ============================================================
    // VÍ DỤ 2: TẠO STATIC VA (dùng lâu dài cho 1 khách hàng)
    // ============================================================
    
    echo "\n\n=== Ví dụ 2: Tạo Static VA ===\n\n";
    
    $customerId = 'CUSTOMER_' . rand(10000, 99999);
    $expireDate = date('Y-m-d H:i:s', strtotime('+1 year')); // Hết hạn sau 1 năm
    
    echo "Mã khách hàng: {$customerId}\n";
    echo "Ngày hết hạn: {$expireDate}\n\n";
    
    echo "Đang gọi API tạo Static VA...\n";
    
    $result2 = $vaService->createStaticVA(
        'NGUYEN VAN B',           // Tên chủ tài khoản
        $customerId,               // Mã định danh khách hàng
        $expireDate,               // Ngày hết hạn
        10000,                     // Số tiền tối thiểu: 10,000 VND
        100000000                  // Số tiền tối đa: 100,000,000 VND
    );
    
    echo "\nKết quả:\n";
    echo "Success: " . ($result2['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result2['code'] . " - " . ErrorCode::getMessage($result2['code']) . "\n";
    
    if ($result2['success'] && $result2['data']) {
        $va = $result2['data'];
        echo "\n=== Thông tin VA ===\n";
        echo "Số VA: " . (isset($va['acc_no']) ? $va['acc_no'] : 'N/A') . "\n";
        echo "Tên ngân hàng: " . (isset($va['bank_name']) ? $va['bank_name'] : 'N/A') . "\n";
        
        echo "\n=> Static VA này có thể dùng cho nhiều giao dịch của cùng 1 khách hàng.\n";
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
