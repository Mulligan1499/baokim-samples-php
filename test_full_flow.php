<?php
/**
 * Test Full API Flow - Baokim B2B
 * 
 * Script này chạy test tất cả các API theo thứ tự:
 * 1. Lấy Token
 * 2. Tạo đơn hàng thường
 * 3. Tra cứu đơn hàng
 * 4. Tạo Dynamic VA
 * 5. Tra cứu giao dịch VA (bank-transfer/detail)
 * 6. Tạo đơn Thu hộ tự động (payment_method=22)
 * 7. Hủy thu hộ tự động
 * 8. Hoàn tiền (nếu đơn đã thanh toán)
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

$refundOrderId = isset($argv[1]) ? $argv[1] : null;
$refundAmount = isset($argv[2]) ? (int)$argv[2] : null;
$autoDebitToken = isset($argv[3]) ? $argv[3] : null;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       BAOKIM B2B API - FULL TEST FLOW                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Track results
$results = [];

try {
    Config::load();
    
    echo "📌 Environment: " . Config::get('base_url') . "\n";
    echo "📌 Merchant: " . Config::get('merchant_code') . "\n\n";
    
    // ============================================================
    // 1. TEST LẤY TOKEN
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [1/8] LẤY ACCESS TOKEN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $auth = new BaokimAuth();
    $token = $auth->getToken();
    $results['token'] = true;
    
    echo "✅ Token: " . substr($token, 0, 50) . "...\n\n";
    
    // ============================================================
    // 2. TEST TẠO ĐƠN HÀNG THƯỜNG
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [2/8] TẠO ĐƠN HÀNG THƯỜNG\n";
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
    // 3. TEST TRA CỨU ĐƠN HÀNG
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [3/8] TRA CỨU ĐƠN HÀNG\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
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
    // 4. TEST TẠO DYNAMIC VA (Host to Host)
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [4/8] TẠO DYNAMIC VA (Host to Host)\n";
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
    $results['create_va'] = $vaResult['success'];
    
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
    // 5. TEST TRA CỨU GIAO DỊCH VA (bank-transfer/detail)
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [5/8] TRA CỨU GIAO DỊCH VA (bank-transfer/detail)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $vaQueryResult = null;
    if ($vaNumber) {
        $vaQueryResult = $vaService->queryTransaction(['acc_no' => $vaNumber]);
        $results['query_va'] = $vaQueryResult['success'];
        
        if ($vaQueryResult['success']) {
            echo "✅ Tra cứu VA thành công!\n";
            echo "   Endpoint: /bank-transfer/detail\n";
            echo "   VA: " . $vaQueryResult['data']['va_info']['acc_no'] . "\n";
            echo "   Bank: " . $vaQueryResult['data']['va_info']['bank_name'] . "\n";
            $txCount = count($vaQueryResult['data']['transactions']);
            echo "   Transactions: " . $txCount . "\n\n";
        } else {
            echo "❌ Lỗi: " . $vaQueryResult['message'] . "\n\n";
        }
    } else {
        $results['query_va'] = false;
        echo "⚠️ Bỏ qua vì không có VA number\n\n";
    }
    
    // ============================================================
    // 6. TEST TẠO ĐƠN THU HỘ TỰ ĐỘNG (payment_method=22)
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [6/8] TẠO ĐƠN THU HỘ TỰ ĐỘNG (payment_method=22)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $autoDebitOrderId = 'TT' . time();
    
    $autoDebitResult = $orderService->createOrder([
        'mrc_order_id' => $autoDebitOrderId,
        'total_amount' => 0,  // Thu hộ tự động có thể là 0
        'description' => 'Don hang Test ' . $autoDebitOrderId,
        'payment_method' => BaokimOrder::PAYMENT_METHOD_AUTO_DEBIT, // 22
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
    // 7. TEST HỦY THU HỘ TỰ ĐỘNG
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [7/8] HỦY THU HỘ TỰ ĐỘNG\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
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
        echo "⚠️ Để test hủy thu hộ tự động, chạy:\n";
        echo "   php test_full_flow.php ORDER_ID AMOUNT AUTO_DEBIT_TOKEN\n";
        echo "   Token được nhận từ webhook khi đăng ký thu hộ tự động thành công\n\n";
    }
    
    // ============================================================
    // 8. TEST HOÀN TIỀN
    // ============================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 [8/8] HOÀN TIỀN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
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
    echo "   [1] Token: ✅\n";
    echo "   [2] Create Order: " . ($results['create_order'] ? '✅' : '❌') . " ($mrcOrderId)\n";
    echo "   [3] Query Order: " . ($results['query_order'] ? '✅' : '❌') . "\n";
    echo "   [4] Create VA (H2H): " . ($results['create_va'] ? '✅' : '❌') . ($vaNumber ? " ($vaNumber)" : '') . "\n";
    echo "   [5] Query VA (H2H): " . ($results['query_va'] ? '✅' : '❌') . "\n";
    echo "   [6] Auto Debit Order: " . ($results['auto_debit'] ? '✅' : '❌') . " ($autoDebitOrderId)\n";
    echo "   [7] Cancel Auto Debit: " . ($results['cancel_auto_debit'] === 'skipped' ? '⏭️ Skipped' : ($results['cancel_auto_debit'] ? '✅' : '❌')) . "\n";
    echo "   [8] Refund: " . ($results['refund'] === 'skipped' ? '⏭️ Skipped' : ($results['refund'] ? '✅' : '❌')) . "\n\n";
    
    echo "📁 Log file: logs/api_" . date('Y-m-d') . ".log\n";
    
} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
