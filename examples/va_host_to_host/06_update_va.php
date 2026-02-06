<?php
/**
 * Ví dụ 6: Cập nhật Virtual Account (VA Host to Host)
 * 
 * Demo cách sử dụng BaokimVA để cập nhật thông tin VA
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\HostToHost\BaokimVA;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Cập Nhật Virtual Account ===\n\n";

// Số VA cần cập nhật (thay bằng số VA thực tế)
$accNo = isset($argv[1]) ? $argv[1] : 'VA0001234567890';

echo "Số VA cập nhật: {$accNo}\n";
echo "(Sử dụng: php 06_update_va.php VA_NUMBER)\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $vaService = new BaokimVA($auth);
    
    // ============================================================
    // CẬP NHẬT THÔNG TIN VA
    // ============================================================
    
    echo "Đang cập nhật thông tin VA...\n\n";
    
    // Dữ liệu cập nhật mẫu
    $updateData = [
        'acc_name' => 'NGUYEN VAN A UPDATED',           // Cập nhật tên
        'collect_amount_min' => 50000,                   // Cập nhật số tiền tối thiểu
        'collect_amount_max' => 50000000,                // Cập nhật số tiền tối đa
        'expire_date' => date('Y-m-d H:i:s', strtotime('+2 years')), // Gia hạn thêm
        // 'status' => 0,  // Uncomment để vô hiệu hóa VA
    ];
    
    echo "Dữ liệu cập nhật:\n";
    print_r($updateData);
    echo "\n";
    
    $result = $vaService->updateVA($accNo, $updateData);
    
    // ============================================================
    // XỬ LÝ KẾT QUẢ
    // ============================================================
    
    echo "=== Kết quả ===\n";
    echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    echo "Message: " . $result['message'] . "\n\n";
    
    if ($result['success']) {
        echo "✓ Cập nhật VA thành công!\n";
        
        if ($result['data']) {
            echo "\nThông tin VA sau cập nhật:\n";
            print_r($result['data']);
        }
    } else {
        echo "✗ Cập nhật VA thất bại!\n";
        echo "Vui lòng kiểm tra lại số VA và quyền truy cập.\n";
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
}
