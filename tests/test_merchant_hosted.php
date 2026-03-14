<?php
/**
 * Test Merchant Hosted VA (Direct Connection)
 * 
 * Test VA APIs: Create Dynamic/Static VA, Update VA, Detail VA
 * 
 * Usage:
 *   php tests/test_merchant_hosted.php
 * 
 * @package Baokim\B2B\Tests
 */

require_once __DIR__ . '/../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MerchantHosted\BaokimMerchantVA;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║    BAOKIM B2B - TEST MERCHANT HOSTED VA (DIRECT)        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$results = [];
$dynamicVaNumber = null;
$staticVaNumber = null;
$dynamicOrderId = null;

try {
    Config::load();
    
    $merchantCode = Config::get('direct_merchant_code') ?: Config::get('merchant_code');
    echo "📌 Environment: " . Config::get('base_url') . "\n";
    echo "📌 Merchant Code: " . $merchantCode . "\n\n";
    
    // ============================================================
    // 1. GET TOKEN (Direct Connection)
    // ============================================================
    printSection(1, 5, "LẤY ACCESS TOKEN (DIRECT)");
    
    $directAuth = BaokimAuth::forDirectConnection();
    $token = $directAuth->getToken();
    $results['token'] = true;
    
    echo "✅ Token: " . substr($token, 0, 50) . "...\n\n";
    
    // ============================================================
    // 2. CREATE DYNAMIC VA
    // ============================================================
    printSection(2, 5, "TẠO DYNAMIC VA (MERCHANT HOSTED)");
    
    $vaService = new BaokimMerchantVA($token);
    $dynamicOrderId = 'MH_DYN_' . date('YmdHis') . '_' . rand(1000, 9999);
    $amount = 100000;
    
    $dynamicVaResult = $vaService->createDynamicVA(
        'NGUYEN VAN A',
        $dynamicOrderId,
        $amount,
        'Test Dynamic VA Merchant Hosted ' . $dynamicOrderId
    );
    
    $results['dynamic_va'] = $dynamicVaResult['success'];
    
    if ($dynamicVaResult['success']) {
        $dynamicVaNumber = $dynamicVaResult['data']['acc_no'];
        echo "✅ Tạo Dynamic VA thành công!\n";
        echo "   VA Number: " . $dynamicVaNumber . "\n";
        echo "   Bank: " . $dynamicVaResult['data']['bank_name'] . "\n";
        echo "   Account Name: " . $dynamicVaResult['data']['acc_name'] . "\n";
        echo "   Amount: " . number_format($amount) . " VND\n";
        echo "   Order ID: " . $dynamicOrderId . "\n";
        if (isset($dynamicVaResult['data']['qr_path'])) {
            echo "   QR: " . $dynamicVaResult['data']['qr_path'] . "\n";
        }
        echo "\n";
    } else {
        echo "❌ Lỗi: " . $dynamicVaResult['message'] . "\n\n";
    }
    
    // ============================================================
    // 3. CREATE STATIC VA
    // ============================================================
    printSection(3, 5, "TẠO STATIC VA (MERCHANT HOSTED)");
    
    $staticOrderId = 'MH_STATIC_' . date('YmdHis') . '_' . rand(1000, 9999);
    $expireDate = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $staticVaResult = $vaService->createStaticVA(
        'TRAN VAN B',
        $staticOrderId,
        $expireDate,
        10000000,  // max amount
        10000      // min amount
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
        echo "   Order ID: " . $staticOrderId . "\n";
        if (isset($staticVaResult['data']['qr_path'])) {
            echo "   QR: " . $staticVaResult['data']['qr_path'] . "\n";
        }
        echo "\n";
    } else {
        echo "❌ Lỗi: " . $staticVaResult['message'] . "\n\n";
    }
    
    // ============================================================
    // 4. UPDATE VA
    // ============================================================
    printSection(4, 5, "CẬP NHẬT VA (MERCHANT HOSTED)");
    
    if ($dynamicOrderId && $results['dynamic_va']) {
        $updateResult = $vaService->updateVA($dynamicOrderId, [
            'acc_name' => 'NGUYEN VAN A UPDATED',
        ]);
        
        $results['update_va'] = $updateResult['success'];
        
        if ($updateResult['success']) {
            echo "✅ Cập nhật VA thành công!\n";
            echo "   Order ID: " . $dynamicOrderId . "\n\n";
        } else {
            echo "❌ Lỗi: " . $updateResult['message'] . "\n\n";
        }
    } else {
        $results['update_va'] = 'skipped';
        echo "⚠️ Skipped - Không có VA để cập nhật\n\n";
    }
    
    // ============================================================
    // 5. DETAIL VA
    // ============================================================
    printSection(5, 5, "TRA CỨU CHI TIẾT VA (MERCHANT HOSTED)");
    
    if ($dynamicVaNumber) {
        $detailResult = $vaService->detailVA($dynamicVaNumber);
        $results['detail_va'] = $detailResult['success'];
        
        if ($detailResult['success']) {
            echo "✅ Tra cứu VA thành công!\n";
            if (isset($detailResult['data']['va_info'])) {
                echo "   VA: " . $detailResult['data']['va_info']['acc_no'] . "\n";
                echo "   Bank: " . $detailResult['data']['va_info']['bank_name'] . "\n";
            }
            if (isset($detailResult['data']['transactions'])) {
                $txCount = count($detailResult['data']['transactions']);
                echo "   Transactions: " . $txCount . "\n";
            }
            echo "\n";
        } else {
            echo "❌ Lỗi: " . $detailResult['message'] . "\n\n";
        }
    } else {
        $results['detail_va'] = 'skipped';
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
    echo "║        MERCHANT HOSTED VA TEST COMPLETED                 ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    
    echo "📋 Summary:\n";
    echo "   [1] Token (Direct): ✅\n";
    echo "   [2] Dynamic VA: " . ($results['dynamic_va'] ? '✅' : '❌') . ($dynamicVaNumber ? " ({$dynamicVaNumber})" : '') . "\n";
    echo "   [3] Static VA: " . ($results['static_va'] ? '✅' : '❌') . ($staticVaNumber ? " ({$staticVaNumber})" : '') . "\n";
    echo "   [4] Update VA: " . ($results['update_va'] === 'skipped' ? '⏭️ Skipped' : ($results['update_va'] ? '✅' : '❌')) . "\n";
    echo "   [5] Detail VA: " . ($results['detail_va'] === 'skipped' ? '⏭️ Skipped' : ($results['detail_va'] ? '✅' : '❌')) . "\n\n";
    
    echo "📁 Log file: logs/api_" . date('Y-m-d') . ".log\n";
}
