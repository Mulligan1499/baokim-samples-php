<?php
/**
 * Test Host-to-Host Connection (VA Management)
 * 
 * Test VA APIs: Create Dynamic/Static VA, Update VA, Query Transaction
 * 
 * Usage:
 *   php tests/test_host_to_host.php
 * 
 * @package Baokim\B2B\Tests
 */

require_once __DIR__ . '/../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\HostToHost\BaokimVA;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       BAOKIM B2B - TEST HOST-TO-HOST (VA)                ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$results = [];
$dynamicVaNumber = null;
$staticVaNumber = null;

try {
    Config::load();
    
    echo "📌 Environment: " . Config::get('base_url') . "\n";
    echo "📌 Master Merchant: " . Config::get('master_merchant_code') . "\n";
    echo "📌 Sub Merchant: " . Config::get('sub_merchant_code') . "\n\n";
    
    // ============================================================
    // 1. GET TOKEN
    // ============================================================
    printSection(1, 5, "LẤY ACCESS TOKEN");
    
    $auth = new BaokimAuth();
    $token = $auth->getToken();
    $results['token'] = true;
    
    echo "✅ Token: " . substr($token, 0, 50) . "...\n\n";
    
    // ============================================================
    // 2. CREATE DYNAMIC VA
    // ============================================================
    printSection(2, 5, "TẠO DYNAMIC VA");
    
    $vaService = new BaokimVA($auth);
    $dynamicOrderId = 'DYN_VA_' . date('YmdHis') . '_' . rand(1000, 9999);
    $amount = 100000;
    
    $dynamicVaResult = $vaService->createDynamicVA(
        'NGUYEN VAN A',
        $dynamicOrderId,
        $amount,
        'Test Dynamic VA ' . $dynamicOrderId
    );
    
    $results['dynamic_va'] = $dynamicVaResult['success'];
    
    if ($dynamicVaResult['success']) {
        $dynamicVaNumber = $dynamicVaResult['data']['acc_no'];
        echo "✅ Tạo Dynamic VA thành công!\n";
        echo "   VA Number: " . $dynamicVaNumber . "\n";
        echo "   Bank: " . $dynamicVaResult['data']['bank_name'] . "\n";
        echo "   Account Name: " . $dynamicVaResult['data']['acc_name'] . "\n";
        echo "   Amount: " . number_format($amount) . " VND\n";
        echo "   QR: " . $dynamicVaResult['data']['qr_path'] . "\n\n";
    } else {
        echo "❌ Lỗi: " . $dynamicVaResult['message'] . "\n\n";
    }
    
    // ============================================================
    // 3. CREATE STATIC VA
    // ============================================================
    printSection(3, 5, "TẠO STATIC VA");
    
    $staticOrderId = 'STATIC_VA_' . date('YmdHis') . '_' . rand(1000, 9999);
    $expireDate = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $staticVaResult = $vaService->createStaticVA(
        'TRAN VAN B',
        $staticOrderId,
        $expireDate,
        10000,    // min amount
        10000000  // max amount
    );
    
    $results['static_va'] = $staticVaResult['success'];
    
    if ($staticVaResult['success']) {
        $staticVaNumber = $staticVaResult['data']['acc_no'];
        echo "✅ Tạo Static VA thành công!\n";
        echo "   VA Number: " . $staticVaNumber . "\n";
        echo "   Bank: " . $staticVaResult['data']['bank_name'] . "\n";
        echo "   Account Name: " . $staticVaResult['data']['acc_name'] . "\n";
        echo "   Amount Range: 10,000 - 10,000,000 VND\n";
        echo "   Expire: " . $expireDate . "\n";
        echo "   QR: " . $staticVaResult['data']['qr_path'] . "\n\n";
    } else {
        echo "❌ Lỗi: " . $staticVaResult['message'] . "\n\n";
    }
    
    // ============================================================
    // 4. UPDATE VA
    // ============================================================
    printSection(4, 5, "CẬP NHẬT VA");
    
    if ($staticVaNumber) {
        $newExpireDate = date('Y-m-d H:i:s', strtotime('+60 days'));
        
        $updateResult = $vaService->updateVA($staticVaNumber, [
            'expire_date' => $newExpireDate,
            'collect_amount_max' => 50000000,
        ]);
        
        $results['update_va'] = $updateResult['success'];
        
        if ($updateResult['success']) {
            echo "✅ Cập nhật VA thành công!\n";
            echo "   VA Number: " . $staticVaNumber . "\n";
            echo "   New Expire: " . $newExpireDate . "\n";
            echo "   New Max Amount: 50,000,000 VND\n\n";
        } else {
            echo "❌ Lỗi: " . $updateResult['message'] . "\n\n";
        }
    } else {
        $results['update_va'] = 'skipped';
        echo "⚠️ Skipped - Không có Static VA để cập nhật\n\n";
    }
    
    // ============================================================
    // 5. QUERY TRANSACTION
    // ============================================================
    printSection(5, 5, "TRA CỨU GIAO DỊCH VA");
    
    if ($dynamicVaNumber) {
        $queryResult = $vaService->queryTransaction(['acc_no' => $dynamicVaNumber]);
        $results['query_transaction'] = $queryResult['success'];
        
        if ($queryResult['success']) {
            echo "✅ Tra cứu VA thành công!\n";
            echo "   VA: " . $queryResult['data']['va_info']['acc_no'] . "\n";
            echo "   Bank: " . $queryResult['data']['va_info']['bank_name'] . "\n";
            $txCount = count($queryResult['data']['transactions']);
            echo "   Transactions: " . $txCount . "\n\n";
        } else {
            echo "❌ Lỗi: " . $queryResult['message'] . "\n\n";
        }
    } else {
        $results['query_transaction'] = 'skipped';
        echo "⚠️ Skipped - Không có VA number để tra cứu\n\n";
    }
    
    // ============================================================
    // SUMMARY
    // ============================================================
    printSummary($results, $dynamicVaNumber, $staticVaNumber);
    
} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function printSection($num, $total, $title) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [{$num}/{$total}] {$title}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}

function printSummary($results, $dynamicVaNumber, $staticVaNumber) {
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║            HOST-TO-HOST TEST COMPLETED                   ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    
    echo "📋 Summary:\n";
    echo "   [1] Token: ✅\n";
    echo "   [2] Dynamic VA: " . ($results['dynamic_va'] ? '✅' : '❌') . ($dynamicVaNumber ? " ({$dynamicVaNumber})" : '') . "\n";
    echo "   [3] Static VA: " . ($results['static_va'] ? '✅' : '❌') . ($staticVaNumber ? " ({$staticVaNumber})" : '') . "\n";
    echo "   [4] Update VA: " . ($results['update_va'] === 'skipped' ? '⏭️ Skipped' : ($results['update_va'] ? '✅' : '❌')) . "\n";
    echo "   [5] Query Transaction: " . ($results['query_transaction'] === 'skipped' ? '⏭️ Skipped' : ($results['query_transaction'] ? '✅' : '❌')) . "\n\n";
    
    echo "📁 Log file: logs/api_" . date('Y-m-d') . ".log\n";
}
