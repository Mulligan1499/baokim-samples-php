<?php
/**
 * Test Full API Flow - Baokim B2B
 * 
 * Unified test script supporting multiple connection types:
 * - basic_pro: MasterSub Order APIs (Create, Query, Refund, Auto Debit)
 * - host_to_host: VA APIs (Create Dynamic/Static VA, Update, Query)
 * - direct: Direct Order APIs (Create, Query, Cancel, Refund)
 * 
 * Usage:
 *   php test_full_flow.php [connection_type] [arg1] [arg2] [arg3]
 * 
 * Examples:
 *   php test_full_flow.php                    # Run all tests
 *   php test_full_flow.php basic_pro          # Test Basic/Pro only
 *   php test_full_flow.php host_to_host       # Test Host-to-Host only
 *   php test_full_flow.php direct             # Test Direct only
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\MasterSub\BaokimOrder;
use Baokim\B2B\HostToHost\BaokimVA;
use Baokim\B2B\Direct\BaokimDirect;

// Parse CLI arguments
$connectionType = isset($argv[1]) ? strtolower($argv[1]) : 'all';
$validTypes = ['all', 'basic_pro', 'host_to_host', 'direct'];

if (!in_array($connectionType, $validTypes)) {
    echo "❌ Invalid connection type: {$connectionType}\n\n";
    echo "Usage: php test_full_flow.php [connection_type]\n\n";
    echo "Valid types:\n";
    echo "  all          - Run all tests (default)\n";
    echo "  basic_pro    - Test MasterSub Order APIs\n";
    echo "  host_to_host - Test Host-to-Host VA APIs\n";
    echo "  direct       - Test Direct Order APIs\n";
    exit(1);
}

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       BAOKIM B2B API - FULL TEST FLOW                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$results = [
    'basic_pro' => [],
    'host_to_host' => [],
    'direct' => [],
];

try {
    Config::load();
    
    echo "📌 Environment: " . Config::get('base_url') . "\n";
    echo "📌 Connection Type: " . strtoupper($connectionType) . "\n\n";
    
    // Get Token (shared)
    $auth = new BaokimAuth();
    $token = $auth->getToken();
    echo "✅ Token acquired successfully\n\n";
    
    // ============================================================
    // BASIC/PRO TESTS
    // ============================================================
    if ($connectionType === 'all' || $connectionType === 'basic_pro') {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔷 BASIC/PRO (MasterSub) TESTS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        $orderService = new BaokimOrder($token);
        $mrcOrderId = 'TEST_' . date('YmdHis') . '_' . rand(1000, 9999);
        
        // Create Order
        $orderResult = $orderService->createOrder([
            'mrc_order_id' => $mrcOrderId,
            'total_amount' => 100000,
            'description' => 'Test order ' . $mrcOrderId,
            'customer_info' => BaokimOrder::buildCustomerInfo('NGUYEN VAN A', 'test@example.com', '0901234567', '123 Test Street'),
        ]);
        $results['basic_pro']['create_order'] = $orderResult['success'];
        echo "   Create Order: " . ($orderResult['success'] ? "✅ {$mrcOrderId}" : "❌ {$orderResult['message']}") . "\n";
        
        // Query Order
        $queryResult = $orderService->queryOrder($mrcOrderId);
        $results['basic_pro']['query_order'] = $queryResult['success'];
        echo "   Query Order: " . ($queryResult['success'] ? "✅" : "❌ {$queryResult['message']}") . "\n";
        
        // Auto Debit Order
        $autoDebitOrderId = 'TT' . time();
        $autoDebitResult = $orderService->createOrder([
            'mrc_order_id' => $autoDebitOrderId,
            'total_amount' => 0,
            'description' => 'Auto debit ' . $autoDebitOrderId,
            'payment_method' => BaokimOrder::PAYMENT_METHOD_AUTO_DEBIT,
            'service_code' => 'QL_THU_HO_1',
            'customer_info' => ['name' => 'NGUYEN VAN A', 'email' => 'test@example.com', 'phone' => '0901234567', 'address' => '123 Test Street', 'gender' => 1],
        ]);
        $results['basic_pro']['auto_debit'] = $autoDebitResult['success'];
        echo "   Auto Debit: " . ($autoDebitResult['success'] ? "✅ {$autoDebitOrderId}" : "❌ {$autoDebitResult['message']}") . "\n\n";
    }
    
    // ============================================================
    // HOST-TO-HOST TESTS
    // ============================================================
    if ($connectionType === 'all' || $connectionType === 'host_to_host') {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔷 HOST-TO-HOST (VA) TESTS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        $vaService = new BaokimVA($token);
        
        // Create Dynamic VA
        $vaOrderId = 'DVA' . date('mdHis') . rand(100, 999);
        $vaResult = $vaService->createDynamicVA('NGUYEN VAN A', $vaOrderId, 100000);
        $results['host_to_host']['dynamic_va'] = $vaResult['success'];
        $vaNumber = $vaResult['success'] ? $vaResult['data']['acc_no'] : null;
        echo "   Dynamic VA: " . ($vaResult['success'] ? "✅ {$vaNumber}" : "❌ {$vaResult['message']}") . "\n";
        
        // Create Static VA
        $staticOrderId = 'SVA' . date('mdHis') . rand(100, 999);
        $staticResult = $vaService->createStaticVA('TRAN VAN B', $staticOrderId, date('Y-m-d H:i:s', strtotime('+30 days')), 10000, 10000000);
        $results['host_to_host']['static_va'] = $staticResult['success'];
        $staticVaNumber = $staticResult['success'] ? $staticResult['data']['acc_no'] : null;
        echo "   Static VA: " . ($staticResult['success'] ? "✅ {$staticVaNumber}" : "❌ {$staticResult['message']}") . "\n";
        
        // Query VA
        if ($vaNumber) {
            $queryVaResult = $vaService->queryTransaction(['acc_no' => $vaNumber]);
            $results['host_to_host']['query_va'] = $queryVaResult['success'];
            echo "   Query VA: " . ($queryVaResult['success'] ? "✅" : "❌ {$queryVaResult['message']}") . "\n\n";
        } else {
            $results['host_to_host']['query_va'] = false;
            echo "   Query VA: ⏭️ Skipped\n\n";
        }
    }
    
    // ============================================================
    // DIRECT TESTS
    // ============================================================
    if ($connectionType === 'all' || $connectionType === 'direct') {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔷 DIRECT CONNECTION TESTS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // Direct connection uses different credentials to get token
        $directAuth = BaokimAuth::forDirectConnection();
        $directToken = $directAuth->getToken();
        $directService = new BaokimDirect($directToken);
        $directOrderId = 'DRT' . date('mdHis') . rand(100, 999);
        
        // Create Order
        $directOrderResult = $directService->createOrder([
            'mrc_order_id' => $directOrderId,
            'total_amount' => 100000,
            'description' => 'Direct order ' . $directOrderId,
            'customer_info' => BaokimDirect::buildCustomerInfo('NGUYEN VAN A', 'test@example.com', '0901234567', '123 Test Street'),
        ]);
        $results['direct']['create_order'] = $directOrderResult['success'];
        echo "   Create Order: " . ($directOrderResult['success'] ? "✅ {$directOrderId}" : "❌ {$directOrderResult['message']}") . "\n";
        
        // Query Order
        $directQueryResult = $directService->queryOrder($directOrderId);
        $results['direct']['query_order'] = $directQueryResult['success'];
        echo "   Query Order: " . ($directQueryResult['success'] ? "✅" : "❌ {$directQueryResult['message']}") . "\n";
        
        // Cancel Order (create new order then cancel)
        $cancelOrderId = 'CXL' . date('mdHis') . rand(100, 999);
        $cancelCreateResult = $directService->createOrder([
            'mrc_order_id' => $cancelOrderId,
            'total_amount' => 50000,
            'description' => 'Order to cancel',
            'customer_info' => BaokimDirect::buildCustomerInfo('TRAN VAN B', 'cancel@example.com', '0901234567', '456 Cancel Street'),
        ]);
        if ($cancelCreateResult['success']) {
            $cancelResult = $directService->cancelOrder($cancelOrderId);
            $results['direct']['cancel_order'] = $cancelResult['success'];
            echo "   Cancel Order: " . ($cancelResult['success'] ? "✅" : "❌ {$cancelResult['message']}") . "\n\n";
        } else {
            $results['direct']['cancel_order'] = false;
            echo "   Cancel Order: ❌ Could not create order\n\n";
        }
    }
    
    // ============================================================
    // SUMMARY
    // ============================================================
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║                    TEST COMPLETED                        ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    
    echo "📋 Summary:\n";
    
    if ($connectionType === 'all' || $connectionType === 'basic_pro') {
        echo "\n   🔷 BASIC/PRO:\n";
        foreach ($results['basic_pro'] as $test => $success) {
            echo "      " . ucwords(str_replace('_', ' ', $test)) . ": " . ($success ? '✅' : '❌') . "\n";
        }
    }
    
    if ($connectionType === 'all' || $connectionType === 'host_to_host') {
        echo "\n   🔷 HOST-TO-HOST:\n";
        foreach ($results['host_to_host'] as $test => $success) {
            echo "      " . ucwords(str_replace('_', ' ', $test)) . ": " . ($success ? '✅' : '❌') . "\n";
        }
    }
    
    if ($connectionType === 'all' || $connectionType === 'direct') {
        echo "\n   🔷 DIRECT:\n";
        foreach ($results['direct'] as $test => $success) {
            echo "      " . ucwords(str_replace('_', ' ', $test)) . ": " . ($success ? '✅' : '❌') . "\n";
        }
    }
    
    echo "\n📁 Log file: logs/api_" . date('Y-m-d') . ".log\n";
    
} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
