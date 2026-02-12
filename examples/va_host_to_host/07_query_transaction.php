<?php
/**
 * Ví dụ 7: Tra cứu giao dịch VA (VA Host to Host)
 * 
 * Demo cách sử dụng BaokimVA để tra cứu giao dịch
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

echo "=== Baokim B2B - Tra Cứu Giao Dịch VA ===\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $vaService = new BaokimVA($auth);
    
    // ============================================================
    // VÍ DỤ 1: TRA CỨU THEO SỐ VA
    // ============================================================
    
    echo "=== Ví dụ 1: Tra cứu theo số VA ===\n\n";
    
    $accNo = isset($argv[1]) ? $argv[1] : 'VA0001234567890';
    
    echo "Số VA: {$accNo}\n";
    echo "Đang tra cứu...\n\n";
    
    $result1 = $vaService->queryTransaction([
        'acc_no' => $accNo,
    ]);
    
    echo "Kết quả:\n";
    echo "Success: " . ($result1['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result1['code'] . "\n\n";
    
    if ($result1['success'] && $result1['data']) {
        print_r($result1['data']);
    }
    
    // ============================================================
    // VÍ DỤ 2: TRA CỨU THEO MÃ ĐƠN HÀNG
    // ============================================================
    
    echo "\n=== Ví dụ 2: Tra cứu theo mã đơn hàng ===\n\n";
    
    $mrcOrderId = isset($argv[2]) ? $argv[2] : 'VA_ORDER_20240101_1234';
    
    echo "Mã đơn hàng: {$mrcOrderId}\n";
    echo "Đang tra cứu...\n\n";
    
    $result2 = $vaService->queryTransaction([
        'mrc_order_id' => $mrcOrderId,
    ]);
    
    echo "Kết quả:\n";
    echo "Success: " . ($result2['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result2['code'] . "\n\n";
    
    if ($result2['success'] && $result2['data']) {
        print_r($result2['data']);
    }
    
    // ============================================================
    // VÍ DỤ 3: TRA CỨU THEO KHOẢNG THỜI GIAN
    // ============================================================
    
    echo "\n=== Ví dụ 3: Tra cứu theo khoảng thời gian ===\n\n";
    
    $fromDate = date('Y-m-d', strtotime('-7 days'));
    $toDate = date('Y-m-d');
    
    echo "Từ ngày: {$fromDate}\n";
    echo "Đến ngày: {$toDate}\n";
    echo "Đang tra cứu...\n\n";
    
    $result3 = $vaService->queryTransaction([
        'from_date' => $fromDate,
        'to_date' => $toDate,
    ]);
    
    echo "Kết quả:\n";
    echo "Success: " . ($result3['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result3['code'] . "\n\n";
    
    if ($result3['success'] && $result3['data']) {
        if (is_array($result3['data']) && isset($result3['data']['transactions'])) {
            $transactions = $result3['data']['transactions'];
            echo "Tìm thấy " . count($transactions) . " giao dịch.\n\n";
            
            foreach ($transactions as $idx => $trans) {
                echo "--- Giao dịch " . ($idx + 1) . " ---\n";
                echo "Mã GD: " . (isset($trans['id']) ? $trans['id'] : 'N/A') . "\n";
                echo "Số VA: " . (isset($trans['acc_no']) ? $trans['acc_no'] : 'N/A') . "\n";
                echo "Số tiền: " . (isset($trans['amount']) ? number_format($trans['amount']) : 'N/A') . " VND\n";
                echo "Trạng thái: " . (isset($trans['status']) ? $trans['status'] : 'N/A') . "\n";
                echo "Thời gian: " . (isset($trans['completed_at']) ? $trans['completed_at'] : 'N/A') . "\n\n";
            }
        } else {
            print_r($result3['data']);
        }
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    echo "\nSử dụng: php 07_query_transaction.php [VA_NUMBER] [ORDER_ID]\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
}
