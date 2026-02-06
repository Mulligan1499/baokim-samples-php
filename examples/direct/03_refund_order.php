<?php
/**
 * Ví dụ 3: Hoàn Tiền Đơn Hàng (Direct Connection)
 * 
 * Demo cách sử dụng BaokimDirect để hoàn tiền cho đơn hàng
 * 
 * Lưu ý: Direct API yêu cầu description là trường bắt buộc khi hoàn tiền
 * 
 * @package Baokim\B2B\Examples
 */

require_once __DIR__ . '/../../src/autoload.php';

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\Direct\BaokimDirect;
use Baokim\B2B\ErrorCode;

// ============================================================
// BẮT ĐẦU
// ============================================================

echo "=== Baokim B2B - Hoàn Tiền Đơn Hàng (Direct Connection) ===\n\n";

// Thông tin hoàn tiền (thay bằng dữ liệu thực tế)
$mrcOrderId = isset($argv[1]) ? $argv[1] : 'DIRECT_20240101120000_1234';
$refundAmount = isset($argv[2]) ? (int)$argv[2] : null; // null = hoàn toàn bộ
$refundReason = isset($argv[3]) ? $argv[3] : 'Hoàn tiền theo yêu cầu khách hàng';

echo "Mã đơn hàng: {$mrcOrderId}\n";
echo "Số tiền hoàn: " . ($refundAmount !== null ? number_format($refundAmount) . " VND" : "Toàn bộ") . "\n";
echo "Lý do: {$refundReason}\n\n";

echo "(Sử dụng: php 03_refund_order.php ORDER_ID [AMOUNT] [REASON])\n\n";

try {
    // Load config
    Config::load();
    
    // Khởi tạo services
    $auth = new BaokimAuth();
    $directService = new BaokimDirect($auth);
    
    // ============================================================
    // GỌI API HOÀN TIỀN
    // ============================================================
    
    echo "Đang gọi API hoàn tiền...\n\n";
    
    // Direct API: refundOrder($mrcOrderId, $description, $amount, $accountNo, $bankNo)
    // Lưu ý: description là required trong Direct API
    $result = $directService->refundOrder(
        $mrcOrderId,      // required: Mã đơn hàng
        $refundReason,    // required: Nội dung/lý do hoàn tiền
        $refundAmount,    // optional: Số tiền hoàn (null = hoàn toàn bộ)
        null,             // optional: Số tài khoản nhận tiền hoàn (required nếu lỗi 116)
        null              // optional: Mã ngân hàng nhận tiền hoàn (required nếu lỗi 116)
    );
    
    // ============================================================
    // XỬ LÝ KẾT QUẢ
    // ============================================================
    
    echo "=== Kết quả ===\n";
    echo "Success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Code: " . $result['code'] . " - " . ErrorCode::getMessage($result['code']) . "\n";
    echo "Message: " . $result['message'] . "\n\n";
    
    if ($result['success']) {
        echo "✓ Hoàn tiền thành công!\n";
        
        if ($result['data']) {
            echo "\nChi tiết:\n";
            print_r($result['data']);
        }
    } else {
        echo "✗ Hoàn tiền thất bại!\n";
        
        // Xử lý lỗi 116: Cần cung cấp thông tin tài khoản nhận tiền
        if ($result['code'] == 116) {
            echo "\n⚠️ Lỗi 116: Cần cung cấp số tài khoản và mã ngân hàng nhận tiền hoàn.\n";
            echo "Vui lòng sử dụng refundOrder với đầy đủ tham số account_no và bank_no.\n";
        } else {
            echo "Vui lòng kiểm tra lại mã đơn hàng và trạng thái đơn.\n";
        }
    }
    
    echo "\n=== HOÀN THÀNH ===\n";
    
} catch (\Exception $e) {
    echo "\n!!! LỖI !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
}
