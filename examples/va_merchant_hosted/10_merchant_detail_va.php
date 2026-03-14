<?php
/**
 * Ví dụ 10: Tra cứu chi tiết VA - Merchant Hosted (Direct Connection)
 * 
 * Demo cách sử dụng BaokimMerchantVA để tra cứu chi tiết VA
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MerchantHosted\BaokimMerchantVA;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Tra cứu chi tiết VA (Merchant Hosted) ===\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $directAuth = BaokimAuth::forDirectConnection();
    $vaService = new BaokimMerchantVA($directAuth->getToken());
    
    // ============================================================
    // TRA CỨU CHI TIẾT VA
    // ============================================================
    
    // Số VA cần tra cứu (thay bằng acc_no thật từ API tạo VA)
    $accNo = $argv[1] ?? '00812345678901';
    
    echo "Số VA: {$accNo}\n\n";
    
    // --- Ví dụ 1: Tra cứu cơ bản ---
    echo "=== Tra cứu cơ bản ===\n";
    
    $result = $vaService->detailVA($accNo);
    
    echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    
    if ($result['success'] && $result['data']) {
        echo "\n=== Thông tin VA ===\n";
        if (isset($result['data']['va_info'])) {
            $vaInfo = $result['data']['va_info'];
            echo "Số VA: " . (isset($vaInfo['acc_no']) ? $vaInfo['acc_no'] : 'N/A') . "\n";
            echo "Tên: " . (isset($vaInfo['acc_name']) ? $vaInfo['acc_name'] : 'N/A') . "\n";
            echo "Ngân hàng: " . (isset($vaInfo['bank_name']) ? $vaInfo['bank_name'] : 'N/A') . "\n";
        }
        
        if (isset($result['data']['transactions'])) {
            $txCount = count($result['data']['transactions']);
            echo "\nSố giao dịch: {$txCount}\n";
            
            foreach ($result['data']['transactions'] as $i => $tx) {
                echo "  [{$i}] Amount: " . (isset($tx['amount']) ? number_format($tx['amount']) : 'N/A') . " VND";
                echo " - Date: " . (isset($tx['created_at']) ? $tx['created_at'] : 'N/A') . "\n";
            }
        }
    }
    
    // --- Ví dụ 2: Tra cứu với bộ lọc thời gian ---
    echo "\n=== Tra cứu với bộ lọc thời gian ===\n";
    
    $result2 = $vaService->detailVA($accNo, [
        'start_date' => date('Y-m-d H:i:s', strtotime('-30 days')),
        'end_date' => date('Y-m-d H:i:s'),
        'current_page' => 1,
        'per_page' => 10,
    ]);
    
    echo "Success: " . ($result2['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result2['code'] . " - " . ErrorCode::getMessage($result2['code']) . "\n";
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
