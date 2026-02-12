<?php
/**
 * Webhook Receiver - Endpoint nhận thông báo từ Baokim
 * 
 * File này được deploy lên server để nhận webhook từ Baokim khi:
 * - Có giao dịch thanh toán thành công
 * - Có giao dịch hoàn tiền
 * - Có giao dịch VA thành công
 * 
 * URL endpoint này cần được đăng ký với Baokim.
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\WebhookHandler;

// ============================================================
// XỬ LÝ WEBHOOK
// ============================================================

try {
    // Load config
    Config::load();
    
    // Khởi tạo webhook handler
    $handler = new WebhookHandler();
    
    // ============================================================
    // ĐĂNG KÝ CUSTOM HANDLERS (Optional)
    // ============================================================
    
    // Handler cho thanh toán thành công
    $handler->onPayment(function($paymentData, $fullPayload) {
        /**
         * XỬ LÝ THANH TOÁN THÀNH CÔNG
         * 
         * $paymentData chứa thông tin giao dịch:
         * - Với Basic Pro: chứa payment_result với order và transactions
         * - Với VA H2H: chứa transaction và va_info
         */
        
        // Log thông tin
        error_log("=== PAYMENT WEBHOOK RECEIVED ===");
        error_log("Data: " . json_encode($paymentData));
        
        // Xử lý VA payment
        if (isset($paymentData['transaction'])) {
            $transaction = $paymentData['transaction'];
            $vaInfo = isset($paymentData['va_info']) ? $paymentData['va_info'] : [];
            
            $mrcOrderId = isset($transaction['mrc_order_id']) ? $transaction['mrc_order_id'] : null;
            $amount = isset($transaction['amount']) ? $transaction['amount'] : 0;
            $status = isset($transaction['status']) ? $transaction['status'] : 0;
            
            if ($mrcOrderId && $status == 1) {
                // TODO: Cập nhật trạng thái đơn hàng trong database
                // updateOrderStatus($mrcOrderId, 'PAID', $amount);
                
                error_log("VA Payment success for order: {$mrcOrderId}, amount: {$amount}");
            }
        }
        
        // Xử lý Basic Pro payment
        if (isset($paymentData['order'])) {
            $order = $paymentData['order'];
            $mrcOrderId = isset($order['mrc_order_id']) ? $order['mrc_order_id'] : null;
            
            if ($mrcOrderId) {
                // TODO: Cập nhật trạng thái đơn hàng trong database
                // updateOrderStatus($mrcOrderId, 'PAID');
                
                error_log("Basic Pro Payment success for order: {$mrcOrderId}");
            }
        }
        
        // Return null để dùng default success response
        // Hoặc return custom response: ['code' => 0, 'message' => 'OK']
        return null;
    });
    
    // Handler cho hoàn tiền
    $handler->onRefund(function($refundData, $fullPayload) {
        /**
         * XỬ LÝ HOÀN TIỀN
         */
        
        error_log("=== REFUND WEBHOOK RECEIVED ===");
        error_log("Data: " . json_encode($refundData));
        
        if (isset($refundData['order'])) {
            $order = $refundData['order'];
            $mrcOrderId = isset($order['mrc_order_id']) ? $order['mrc_order_id'] : null;
            
            if ($mrcOrderId) {
                // TODO: Cập nhật trạng thái đơn hàng
                // updateOrderStatus($mrcOrderId, 'REFUNDED');
                
                error_log("Refund success for order: {$mrcOrderId}");
            }
        }
        
        return null;
    });
    
    // ============================================================
    // XỬ LÝ VÀ TRẢ RESPONSE
    // ============================================================
    
    // Xử lý webhook (tự động verify signature)
    $response = $handler->handle(true);
    
    // Gửi response về cho Baokim
    $handler->sendResponse($response);
    
} catch (\Exception $e) {
    // Log lỗi
    error_log("Webhook Error: " . $e->getMessage());
    
    // Trả về error response
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 500,
        'message' => 'Internal server error',
    ]);
}
