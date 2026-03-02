<?php
/**
 * Test Basic/Pro (MasterSub) Connection
 * 
 * Test Order APIs: Create, Query, Refund, Cancel Auto Debit
 * 
 * Usage:
 *   php tests/test_basic_pro.php [refund_order_id] [refund_amount] [auto_debit_token]
 * 
 * @package Baokim\B2B\Tests
 */

require_once __DIR__ . '/../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MasterSub\BaokimOrder;

// Parse CLI arguments
$refundOrderId = isset($argv[1]) ? $argv[1] : null;
$refundAmount = isset($argv[2]) ? (int)$argv[2] : null;
$autoDebitToken = isset($argv[3]) ? $argv[3] : null;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       BAOKIM B2B - TEST BASIC/PRO (MasterSub)            ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$results = [];

try {
    Config::load();
    
    echo "📌 Environment: " . Config::get('base_url') . "\n";
    echo "📌 Master Merchant: " . Config::get('master_merchant_code') . "\n";
    echo "📌 Sub Merchant: " . Config::get('sub_merchant_code') . "\n\n";
    
    // ============================================================
    // 1. GET TOKEN
    // ============================================================
    printSection(1, 6, "LẤY ACCESS TOKEN");
    
    $auth = new BaokimAuth();
    $token = $auth->getToken();
    $results['token'] = true;
    
    echo "✅ Token: " . substr($token, 0, 50) . "...\n\n";
    
    // ============================================================
    // 2. CREATE ORDER
    // ============================================================
    printSection(2, 6, "TẠO ĐƠN HÀNG THƯỜNG");
    
    $orderService = new BaokimOrder($token);
    $mrcOrderId = 'BASIC_' . date('YmdHis') . '_' . rand(1000, 9999);
    $amount = 100000;
    
    $orderResult = $orderService->createOrder([
        'mrc_order_id' => $mrcOrderId,
        'total_amount' => $amount,
        'description' => 'Test Basic/Pro order ' . $mrcOrderId,
        'customer_info' => BaokimOrder::buildCustomerInfo(
            'Nguyen Van A',
            'test@example.com',
            '0901234567',
            '123 Test Street'
        ),
        'url_success' => Config::get('url_success'),
        'url_fail' => Config::get('url_fail'),
    ]);
    
    $results['create_order'] = $orderResult['success'];
    
    if ($orderResult['success']) {
        echo "✅ Tạo đơn thành công!\n";
        echo "   Order ID: " . $orderResult['data']['order_id'] . "\n";
        echo "   MRC Order ID: " . $mrcOrderId . "\n";
        echo "   Amount: " . number_format($amount) . " VND\n";
        echo "   Payment URL: " . $orderResult['data']['redirect_url'] . "\n\n";
    } else {
        echo "❌ Lỗi: " . $orderResult['message'] . "\n\n";
    }
    
    // ============================================================
    // 3. QUERY ORDER
    // ============================================================
    printSection(3, 6, "TRA CỨU ĐƠN HÀNG");
    
    $queryResult = $orderService->queryOrder($mrcOrderId);
    $results['query_order'] = $queryResult['success'];
    
    if ($queryResult['success']) {
        $order = $queryResult['data']['order'];
        echo "✅ Tra cứu thành công!\n";
        echo "   Order ID: " . $order['id'] . "\n";
        echo "   Status: " . $order['status'] . " (" . ($order['status'] == 1 ? 'Đã thanh toán' : 'Chưa thanh toán') . ")\n";
        echo "   Amount: " . number_format($order['total_amount']) . " VND\n\n";
    } else {
        echo "❌ Lỗi: " . $queryResult['message'] . "\n\n";
    }
    
    // ============================================================
    // 4. CREATE AUTO DEBIT ORDER
    // ============================================================
    printSection(4, 6, "TẠO ĐƠN THU HỘ TỰ ĐỘNG (payment_method=22)");
    
    $autoDebitOrderId = 'TT' . time();
    
    $autoDebitResult = $orderService->createOrder([
        'mrc_order_id' => $autoDebitOrderId,
        'total_amount' => 0,
        'description' => 'Don hang Thu ho tu dong ' . $autoDebitOrderId,
        'payment_method' => BaokimOrder::PAYMENT_METHOD_AUTO_DEBIT,
        'service_code' => 'QL_THU_HO_1',
        'save_token' => 0,
        'items' => [
            [
                'code' => 'PROD001',
                'name' => 'San pham A',
                'amount' => 0,
                'quantity' => 1,
                'link' => 'https://example.com/product-a',
            ],
        ],
        'customer_info' => [
            'code' => 'KH01',
            'name' => 'AUTOMATION TEST',
            'email' => 'test@example.com',
            'phone' => '0911830977',
            'address' => '123 Nguyen Trai, Hanoi',
            'gender' => 1,
        ],
        'url_success' => Config::get('url_success'),
        'url_fail' => Config::get('url_fail'),
    ]);
    
    $results['auto_debit'] = $autoDebitResult['success'];
    
    if ($autoDebitResult['success']) {
        echo "✅ Tạo đơn Thu hộ tự động thành công!\n";
        echo "   Order ID: " . $autoDebitResult['data']['order_id'] . "\n";
        echo "   MRC Order ID: " . $autoDebitOrderId . "\n";
        echo "   Payment Method: 22 (Thu hộ tự động)\n";
        echo "   Redirect URL: " . $autoDebitResult['data']['redirect_url'] . "\n\n";
    } else {
        echo "❌ Lỗi: " . $autoDebitResult['message'] . "\n";
        echo "   Code: " . $autoDebitResult['code'] . "\n\n";
    }
    
    // ============================================================
    // 5. CANCEL AUTO DEBIT
    // ============================================================
    printSection(5, 6, "HỦY THU HỘ TỰ ĐỘNG");
    
    if ($autoDebitToken) {
        echo "   Token: " . substr($autoDebitToken, 0, 20) . "...\n";
        
        $cancelResult = $orderService->cancelAutoDebit($autoDebitToken);
        $results['cancel_auto_debit'] = $cancelResult['success'];
        
        if ($cancelResult['success']) {
            echo "✅ Hủy thu hộ tự động thành công!\n";
            echo "   Code: " . $cancelResult['code'] . "\n";
            echo "   Message: " . $cancelResult['message'] . "\n\n";
        } else {
            echo "❌ Lỗi: " . $cancelResult['message'] . "\n\n";
        }
    } else {
        $results['cancel_auto_debit'] = 'skipped';
        echo "⚠️ Skipped - Cần truyền AUTO_DEBIT_TOKEN\n";
        echo "   php tests/test_basic_pro.php ORDER_ID AMOUNT AUTO_DEBIT_TOKEN\n\n";
    }
    
    // ============================================================
    // 6. REFUND ORDER
    // ============================================================
    printSection(6, 6, "HOÀN TIỀN");
    
    if ($refundOrderId && $refundAmount) {
        echo "   Order ID: " . $refundOrderId . "\n";
        echo "   Amount: " . number_format($refundAmount) . " VND\n";
        
        $refundResult = $orderService->refundOrder($refundOrderId, $refundAmount, 'Test refund');
        $results['refund'] = $refundResult['success'];
        
        if ($refundResult['success']) {
            echo "✅ Hoàn tiền thành công!\n";
            echo "   Code: " . $refundResult['code'] . "\n";
            echo "   Message: " . $refundResult['message'] . "\n\n";
        } else {
            echo "❌ Lỗi: " . $refundResult['message'] . "\n\n";
        }
    } else {
        $results['refund'] = 'skipped';
        echo "⚠️ Skipped - Cần truyền ORDER_ID và AMOUNT\n";
        echo "   php tests/test_basic_pro.php ORDER_ID AMOUNT\n\n";
    }
    
    // ============================================================
    // SUMMARY
    // ============================================================
    printSummary($results, $mrcOrderId, $autoDebitOrderId);
    
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

function printSummary($results, $mrcOrderId, $autoDebitOrderId) {
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║              BASIC/PRO TEST COMPLETED                    ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    
    echo "📋 Summary:\n";
    echo "   [1] Token: ✅\n";
    echo "   [2] Create Order: " . ($results['create_order'] ? '✅' : '❌') . " ({$mrcOrderId})\n";
    echo "   [3] Query Order: " . ($results['query_order'] ? '✅' : '❌') . "\n";
    echo "   [4] Auto Debit Order: " . ($results['auto_debit'] ? '✅' : '❌') . " ({$autoDebitOrderId})\n";
    echo "   [5] Cancel Auto Debit: " . ($results['cancel_auto_debit'] === 'skipped' ? '⏭️ Skipped' : ($results['cancel_auto_debit'] ? '✅' : '❌')) . "\n";
    echo "   [6] Refund: " . ($results['refund'] === 'skipped' ? '⏭️ Skipped' : ($results['refund'] ? '✅' : '❌')) . "\n\n";
    
    echo "📁 Log file: logs/api_" . date('Y-m-d') . ".log\n";
}
