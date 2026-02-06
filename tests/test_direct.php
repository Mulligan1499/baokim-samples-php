<?php
/**
 * Test Direct Connection
 * 
 * Test Direct Order APIs: Create, Query, Cancel, Refund
 * 
 * Usage:
 *   php tests/test_direct.php [refund_order_id] [refund_amount]
 * 
 * @package Baokim\B2B\Tests
 */

require_once __DIR__ . '/../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\Direct\BaokimDirect;

// Parse CLI arguments
$refundOrderId = isset($argv[1]) ? $argv[1] : null;
$refundAmount = isset($argv[2]) ? (int)$argv[2] : null;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       BAOKIM B2B - TEST DIRECT CONNECTION                ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$results = [];

try {
    Config::load();
    
    echo "📌 Environment: " . Config::get('base_url') . "\n";
    echo "📌 Merchant: " . Config::get('merchant_code') . "\n\n";
    
    // ============================================================
    // 1. GET TOKEN
    // ============================================================
    printSection(1, 5, "LẤY ACCESS TOKEN");
    
    $auth = new BaokimAuth();
    $token = $auth->getToken();
    $results['token'] = true;
    
    echo "✅ Token: " . substr($token, 0, 50) . "...\n\n";
    
    // ============================================================
    // 2. CREATE ORDER
    // ============================================================
    printSection(2, 5, "TẠO ĐƠN HÀNG (Direct)");
    
    $directService = new BaokimDirect($auth);
    $mrcOrderId = 'DIRECT_' . date('YmdHis') . '_' . rand(1000, 9999);
    $amount = 100000;
    
    $orderResult = $directService->createOrder([
        'mrc_order_id' => $mrcOrderId,
        'total_amount' => $amount,
        'description' => 'Test Direct order ' . $mrcOrderId,
        'customer_info' => BaokimDirect::buildCustomerInfo(
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
    printSection(3, 5, "TRA CỨU ĐƠN HÀNG");
    
    $queryResult = $directService->queryOrder($mrcOrderId);
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
    // 4. CANCEL ORDER
    // ============================================================
    printSection(4, 5, "HỦY ĐƠN HÀNG");
    
    // Create a new order to cancel (can't cancel paid orders)
    $cancelOrderId = 'CANCEL_' . date('YmdHis') . '_' . rand(1000, 9999);
    
    $cancelOrderResult = $directService->createOrder([
        'mrc_order_id' => $cancelOrderId,
        'total_amount' => 50000,
        'description' => 'Order to cancel ' . $cancelOrderId,
        'customer_info' => BaokimDirect::buildCustomerInfo(
            'Test Cancel',
            'cancel@example.com',
            '0901234567'
        ),
    ]);
    
    if ($cancelOrderResult['success']) {
        echo "   Tạo đơn để test hủy: " . $cancelOrderId . "\n";
        
        $cancelResult = $directService->cancelOrder($cancelOrderId);
        $results['cancel_order'] = $cancelResult['success'];
        
        if ($cancelResult['success']) {
            echo "✅ Hủy đơn thành công!\n";
            echo "   Order ID: " . $cancelOrderId . "\n";
            echo "   Message: " . $cancelResult['message'] . "\n\n";
        } else {
            echo "❌ Lỗi: " . $cancelResult['message'] . "\n\n";
        }
    } else {
        $results['cancel_order'] = false;
        echo "❌ Không thể tạo đơn để test hủy: " . $cancelOrderResult['message'] . "\n\n";
    }
    
    // ============================================================
    // 5. REFUND ORDER
    // ============================================================
    printSection(5, 5, "HOÀN TIỀN");
    
    if ($refundOrderId && $refundAmount) {
        echo "   Order ID: " . $refundOrderId . "\n";
        echo "   Amount: " . number_format($refundAmount) . " VND\n";
        
        $refundResult = $directService->refundOrder($refundOrderId, 'Test refund', $refundAmount);
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
        echo "   php tests/test_direct.php ORDER_ID AMOUNT\n\n";
    }
    
    // ============================================================
    // SUMMARY
    // ============================================================
    printSummary($results, $mrcOrderId);
    
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

function printSummary($results, $mrcOrderId) {
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║              DIRECT TEST COMPLETED                       ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    
    echo "📋 Summary:\n";
    echo "   [1] Token: ✅\n";
    echo "   [2] Create Order: " . ($results['create_order'] ? '✅' : '❌') . " ({$mrcOrderId})\n";
    echo "   [3] Query Order: " . ($results['query_order'] ? '✅' : '❌') . "\n";
    echo "   [4] Cancel Order: " . ($results['cancel_order'] ? '✅' : '❌') . "\n";
    echo "   [5] Refund: " . ($results['refund'] === 'skipped' ? '⏭️ Skipped' : ($results['refund'] ? '✅' : '❌')) . "\n\n";
    
    echo "📁 Log file: logs/api_" . date('Y-m-d') . ".log\n";
}
