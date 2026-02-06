<?php
/**
 * Class BaokimOrder
 * 
 * Quản lý đơn hàng qua Baokim B2B API (Master-Sub Connection)
 * 
 * Kết nối Master-Sub sử dụng master_merchant_code và sub_merchant_code
 * 
 * Bao gồm:
 * - Tạo đơn hàng: POST /b2b/core/api/ext/mm/order/send
 * - Tra cứu đơn hàng: POST /b2b/core/api/ext/mm/order/get-order
 * - Hoàn đơn hàng: POST /b2b/core/api/ext/mm/refund/send
 * - Hủy thu hộ tự động: POST /b2b/core/api/ext/mm/autodebit/cancel
 * 
 * @package Baokim\B2B\MasterSub
 */

namespace Baokim\B2B\MasterSub;

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\HttpClient;
use Baokim\B2B\SignatureHelper;

class BaokimOrder
{
    /**
     * @var HttpClient
     */
    private $httpClient;
    
    /**
     * @var BaokimAuth
     */
    private $auth;
    
    /**
     * API Endpoints
     */
    const ENDPOINT_CREATE_ORDER = '/b2b/core/api/ext/mm/order/send';
    const ENDPOINT_QUERY_ORDER = '/b2b/core/api/ext/mm/order/get-order';
    const ENDPOINT_REFUND_ORDER = '/b2b/core/api/ext/mm/refund/send';
    const ENDPOINT_CANCEL_AUTO_DEBIT = '/b2b/core/api/ext/mm/autodebit/cancel';
    
    /**
     * Payment methods
     */
    const PAYMENT_METHOD_VA = 1;           // Virtual Account
    const PAYMENT_METHOD_VNPAY_QR = 6;     // VNPay QR
    const PAYMENT_METHOD_AUTO_DEBIT = 22;  // Thu hộ tự động
    
    /**
     * Constructor
     * 
     * @param BaokimAuth|null $auth
     * @param HttpClient|null $httpClient
     */
    public function __construct(BaokimAuth $auth = null, HttpClient $httpClient = null)
    {
        $this->auth = $auth ?: new BaokimAuth();
        $this->httpClient = $httpClient ?: new HttpClient();
    }
    
    /**
     * Tạo đơn hàng mới
     * 
     * @param array $orderData Thông tin đơn hàng
     *        - mrc_order_id: (string, required) Mã đơn hàng của Merchant
     *        - total_amount: (int, required) Tổng tiền đơn hàng
     *        - description: (string, required) Mô tả đơn hàng (max 120 ký tự)
     *        - payment_method: (int, optional) Phương thức thanh toán
     *        - items: (array, optional) Danh sách sản phẩm
     *        - customer_info: (array, optional) Thông tin khách hàng
     *        - payment_info: (array, optional) Thông tin thanh toán
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function createOrder(array $orderData)
    {
        // Validate required fields
        $requiredFields = ['mrc_order_id', 'total_amount', 'description'];
        foreach ($requiredFields as $field) {
            if (!isset($orderData[$field]) || $orderData[$field] === '' || $orderData[$field] === null) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Tạo request ID duy nhất
        $requestId = $this->generateRequestId();
        
        // Chuẩn bị request body với tất cả các trường
        // Required fields - bắt buộc phải truyền
        // Optional fields - nếu không truyền sẽ để null
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $requestId,
            'request_time' => date('Y-m-d H:i:s'),
            'master_merchant_code' => Config::get('master_merchant_code'),
            'sub_merchant_code' => Config::get('sub_merchant_code'),
            'mrc_order_id' => $orderData['mrc_order_id'],                    // required: Mã đơn hàng của Merchant
            'total_amount' => (int)$orderData['total_amount'],               // required: Tổng tiền đơn hàng
            'description' => $orderData['description'],                       // required: Mô tả đơn hàng (max 120 ký tự)
            'url_success' => isset($orderData['url_success']) 
                ? $orderData['url_success'] 
                : Config::get('url_success'),
            'url_fail' => isset($orderData['url_fail']) 
                ? $orderData['url_fail'] 
                : Config::get('url_fail'),
            
            // === OPTIONAL FIELDS ===
            'payment_method' => isset($orderData['payment_method']) 
                ? (int)$orderData['payment_method'] 
                : null,                                                       // optional: Phương thức thanh toán (1=VA, 6=VNPay QR, 22=Thu hộ tự động)
            'items' => isset($orderData['items']) && is_array($orderData['items']) 
                ? $orderData['items'] 
                : null,                                                       // optional: Danh sách sản phẩm
            'customer_info' => isset($orderData['customer_info']) && is_array($orderData['customer_info']) 
                ? $orderData['customer_info'] 
                : null,                                                       // optional: Thông tin khách hàng
            'payment_info' => isset($orderData['payment_info']) && is_array($orderData['payment_info']) 
                ? $orderData['payment_info'] 
                : null,                                                       // optional: Thông tin thanh toán
            'service_code' => isset($orderData['service_code']) 
                ? $orderData['service_code'] 
                : null,                                                       // optional: Mã dịch vụ (cho Thu hộ tự động)
            'save_token' => isset($orderData['save_token']) 
                ? $orderData['save_token'] 
                : null,                                                       // optional: Lưu token (cho Thu hộ tự động)
            'store_code' => isset($orderData['store_code']) 
                ? $orderData['store_code'] 
                : null,                                                       // optional: Mã cửa hàng
            'branch_code' => isset($orderData['branch_code']) 
                ? $orderData['branch_code'] 
                : null,                                                       // optional: Mã chi nhánh
            'staff_code' => isset($orderData['staff_code']) 
                ? $orderData['staff_code'] 
                : null,                                                       // optional: Mã nhân viên
        ];
        
        // Loại bỏ các field null để tránh lỗi validation từ API
        $requestBody = array_filter($requestBody, function($value) {
            return $value !== null;
        });
        
        return $this->sendRequest(self::ENDPOINT_CREATE_ORDER, $requestBody);
    }
    
    /**
     * Tra cứu thông tin đơn hàng
     * 
     * @param string $mrcOrderId Mã đơn hàng của Merchant
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function queryOrder($mrcOrderId)
    {
        // Validate required field
        if (empty($mrcOrderId)) {
            throw new \Exception("Missing required field: mrc_order_id");
        }
        
        // Chuẩn bị request body
        // Tất cả các trường đều là required
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'master_merchant_code' => Config::get('master_merchant_code'),
            'sub_merchant_code' => Config::get('sub_merchant_code'),
            'mrc_order_id' => $mrcOrderId,                                   // required: Mã đơn hàng của Merchant
        ];
        
        return $this->sendRequest(self::ENDPOINT_QUERY_ORDER, $requestBody);
    }
    
    /**
     * Hoàn tiền đơn hàng
     * 
     * @param string $mrcOrderId Mã đơn hàng của Merchant
     * @param int $amount Số tiền hoàn (0 = hoàn toàn bộ)
     * @param string $description Lý do hoàn tiền
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function refundOrder($mrcOrderId, $amount = null, $description = null)
    {
        // Validate required field
        if (empty($mrcOrderId)) {
            throw new \Exception("Missing required field: mrc_order_id");
        }
        
        // Chuẩn bị request body với tất cả các trường
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'master_merchant_code' => Config::get('master_merchant_code'),
            'sub_merchant_code' => Config::get('sub_merchant_code'),
            'mrc_order_id' => $mrcOrderId,                                   // required: Mã đơn hàng của Merchant
            
            // === OPTIONAL FIELDS ===
            'amount' => $amount !== null ? (int)$amount : null,              // optional: Số tiền hoàn (0 hoặc null = hoàn toàn bộ)
            'description' => $description !== null ? $description : null,    // optional: Lý do hoàn tiền
        ];
        
        return $this->sendRequest(self::ENDPOINT_REFUND_ORDER, $requestBody);
    }
    
    /**
     * Hủy thu hộ tự động
     * 
     * @param string $token Token thẻ/tài khoản thu hộ tự động (từ webhook khi đăng ký thành công)
     * @param string|null $urlSuccess URL redirect khi hủy thành công (optional, lấy từ config nếu không truyền)
     * @param string|null $urlFail URL redirect khi hủy thất bại (optional, lấy từ config nếu không truyền)
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function cancelAutoDebit($token, $urlSuccess = null, $urlFail = null)
    {
        // Validate required field
        if (empty($token)) {
            throw new \Exception("Missing required field: token");
        }
        
        // Chuẩn bị request body với tất cả các trường
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'master_merchant_code' => Config::get('master_merchant_code'),
            'sub_merchant_code' => Config::get('sub_merchant_code'),
            'token' => $token,                                               // required: Token thẻ/tài khoản thu hộ tự động
            
            // === OPTIONAL FIELDS (lấy từ config nếu không truyền) ===
            'url_success' => $urlSuccess !== null 
                ? $urlSuccess 
                : Config::get('url_success'),                                // optional: URL redirect khi hủy thành công
            'url_fail' => $urlFail !== null 
                ? $urlFail 
                : Config::get('url_fail'),                                   // optional: URL redirect khi hủy thất bại
        ];
        
        return $this->sendRequest(self::ENDPOINT_CANCEL_AUTO_DEBIT, $requestBody);
    }
    
    /**
     * Gửi request tới Baokim API
     * 
     * @param string $endpoint
     * @param array $requestBody
     * @return array
     * @throws \Exception
     */
    private function sendRequest($endpoint, array $requestBody)
    {
        // Lấy access token
        $authHeader = $this->auth->getAuthorizationHeader();
        
        // Ký request body
        $jsonBody = json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = SignatureHelper::sign($jsonBody);
        
        // Gửi request
        $response = $this->httpClient->post($endpoint, $requestBody, [
            'Authorization' => $authHeader,
            'Signature' => $signature,
        ]);
        
        // Xử lý response
        if (!$response['success']) {
            throw new \Exception('API request failed: ' . $response['error']);
        }
        
        $data = $response['data'];
        
        // Kiểm tra response code
        $code = isset($data['code']) ? $data['code'] : null;
        
        // Trả về toàn bộ response để caller xử lý
        return [
            'success' => $code === 0 || $code === 100 || $code === 101 || $code === 200,
            'code' => $code,
            'message' => isset($data['message']) ? $data['message'] : '',
            'data' => isset($data['data']) ? $data['data'] : null,
            'raw_response' => $data,
        ];
    }
    
    /**
     * Tạo request ID duy nhất
     * 
     * @return string
     */
    private function generateRequestId()
    {
        return Config::get('merchant_code') . '_' . date('YmdHis') . '_' . uniqid();
    }
    
    /**
     * Helper: Tạo thông tin customer
     * 
     * @param string $name
     * @param string $email
     * @param string $phone
     * @param string $address
     * @param int $gender 1=male, 2=female
     * @return array
     */
    public static function buildCustomerInfo($name, $email, $phone, $address = '', $gender = 1)
    {
        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'gender' => $gender,
        ];
    }
    
    /**
     * Helper: Tạo item sản phẩm
     * 
     * @param string $code Mã sản phẩm
     * @param string $name Tên sản phẩm
     * @param int $amount Đơn giá
     * @param int $quantity Số lượng
     * @param string $link Link sản phẩm (optional)
     * @return array
     */
    public static function buildItem($code, $name, $amount, $quantity = 1, $link = '')
    {
        return [
            'code' => $code,
            'name' => $name,
            'amount' => (int)$amount,
            'quantity' => (int)$quantity,
            'link' => $link,
        ];
    }
}
