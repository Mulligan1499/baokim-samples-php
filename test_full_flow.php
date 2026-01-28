<?php
/**
 * Test Full API Flow - Baokim B2B
 * 
 * Script này chạy test tất cả các API theo thứ tự:
 * 1. Lấy Token
 * 2. Tạo đơn hàng
 * 3. Tra cứu đơn hàng
 * 4. Tạo Dynamic VA
 * 5. Tra cứu giao dịch VA
 * 6. Hoàn tiền (nếu đơn đã thanh toán)
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\BaokimOrder;
use Baokim\B2B\BaokimVA;
use Baokim\B2B\ErrorCode;

// ============================================================
// CONFIGURATION
// ============================================================

// Mã đơn hàng cần refund (truyền qua command line hoặc để trống)
$refundOrderId = isset($argv[1]) ? $argv[1] : null;
$refundAmount = isset($argv[2]) ? (int)$argv[2] : null;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       BAOKIM B2B API - FULL TEST FLOW                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

try {
    Config::load();
    
    echo "📌 Environment: " . Config::get('base_url') . "\n";
    echo "📌 Merchant: " . Config::get('merchant_code') . "\n\n";
    
    // ============================================================
    // 1. TEST LẤY TOKEN
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [1/6] LẤY ACCESS TOKEN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $auth = new BaokimAuth();
    $token = $auth->getToken();
    
    echo "✅ Token: " . substr($token, 0, 50) . "...\n\n";
    
    // ============================================================
    // 2. TEST TẠO ĐƠN HÀNG
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [2/6] TẠO ĐƠN HÀNG\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $orderService = new BaokimOrder($auth);
    $mrcOrderId = 'TEST_' . date('YmdHis') . '_' . rand(1000, 9999);
    $amount = 100000;
    
    $orderResult = $orderService->createOrder([
        'mrc_order_id' => $mrcOrderId,
        'total_amount' => $amount,
        'description' => 'Test order ' . $mrcOrderId,
        'customer_info' => BaokimOrder::buildCustomerInfo(
            'Nguyen Van A',
            'test@example.com',
            '0901234567',
            '123 Test Street'
        ),
        'url_success' => Config::get('url_success'),
        'url_fail' => Config::get('url_fail'),
    ]);
    
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
    // 3. TEST TRA CỨU ĐƠN HÀNG
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [3/6] TRA CỨU ĐƠN HÀNG\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $queryResult = $orderService->queryOrder($mrcOrderId);
    
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
    // 4. TEST TẠO DYNAMIC VA
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [4/6] TẠO DYNAMIC VA\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $vaService = new BaokimVA($auth);
    $vaOrderId = 'VA_' . date('YmdHis') . '_' . rand(1000, 9999);
    $vaAmount = 100000;
    
    $vaResult = $vaService->createDynamicVA(
        'NGUYEN VAN A',
        $vaOrderId,
        $vaAmount,
        'Test VA ' . $vaOrderId
    );
    
    $vaNumber = null;
    if ($vaResult['success']) {
        $vaNumber = $vaResult['data']['acc_no'];
        echo "✅ Tạo VA thành công!\n";
        echo "   VA Number: " . $vaNumber . "\n";
        echo "   Bank: " . $vaResult['data']['bank_name'] . "\n";
        echo "   Account Name: " . $vaResult['data']['acc_name'] . "\n";
        echo "   Amount: " . number_format($vaAmount) . " VND\n";
        echo "   QR: " . $vaResult['data']['qr_path'] . "\n\n";
    } else {
        echo "❌ Lỗi: " . $vaResult['message'] . "\n\n";
    }
    
    // ============================================================
    // 5. TEST TRA CỨU GIAO DỊCH VA
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [5/6] TRA CỨU GIAO DỊCH VA\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($vaNumber) {
        $vaQueryResult = $vaService->queryTransaction(['acc_no' => $vaNumber]);
        
        if ($vaQueryResult['success']) {
            echo "✅ Tra cứu VA thành công!\n";
            echo "   VA: " . $vaQueryResult['data']['va_info']['acc_no'] . "\n";
            echo "   Bank: " . $vaQueryResult['data']['va_info']['bank_name'] . "\n";
            $txCount = count($vaQueryResult['data']['transactions']);
            echo "   Transactions: " . $txCount . "\n\n";
        } else {
            echo "❌ Lỗi: " . $vaQueryResult['message'] . "\n\n";
        }
    } else {
        echo "⚠️ Bỏ qua vì không có VA number\n\n";
    }
    
    // ============================================================
    // 6. TEST HOÀN TIỀN (nếu có order đã thanh toán)
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [6/6] HOÀN TIỀN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($refundOrderId && $refundAmount) {
        echo "   Order ID: " . $refundOrderId . "\n";
        echo "   Amount: " . number_format($refundAmount) . " VND\n";
        
        $refundResult = $orderService->refundOrder($refundOrderId, $refundAmount, 'Test refund');
        
        if ($refundResult['success']) {
            echo "✅ Hoàn tiền thành công!\n";
            echo "   Code: " . $refundResult['code'] . "\n";
            echo "   Message: " . $refundResult['message'] . "\n\n";
        } else {
            echo "❌ Lỗi: " . $refundResult['message'] . "\n\n";
        }
    } else {
        echo "⚠️ Để test refund, chạy:\n";
        echo "   php test_full_flow.php ORDER_ID AMOUNT\n";
        echo "   Ví dụ: php test_full_flow.php ORDER_20260128_1234 100000\n\n";
    }
    
    // ============================================================
    // SUMMARY
    // ============================================================
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║                    TEST COMPLETED                        ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    
    echo "📋 Summary:\n";
    echo "   - Token: ✅\n";
    echo "   - Create Order: " . ($orderResult['success'] ? '✅' : '❌') . " ($mrcOrderId)\n";
    echo "   - Query Order: " . ($queryResult['success'] ? '✅' : '❌') . "\n";
    echo "   - Create VA: " . ($vaResult['success'] ? '✅' : '❌') . ($vaNumber ? " ($vaNumber)" : '') . "\n";
    echo "   - Query VA: " . (isset($vaQueryResult) && $vaQueryResult['success'] ? '✅' : '⚠️') . "\n";
    echo "   - Refund: " . ($refundOrderId ? (isset($refundResult) && $refundResult['success'] ? '✅' : '❌') : '⏭️ Skipped') . "\n\n";
    
    echo "📁 Log file: logs/api_" . date('Y-m-d') . ".log\n";
    
} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
