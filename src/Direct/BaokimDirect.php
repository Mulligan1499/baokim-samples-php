<?php
/**
 * Class BaokimDirect
 * 
 * Quản lý đơn hàng qua Baokim B2B API (Direct/Pro Integration)
 * 
 * Bao gồm:
 * - Tạo đơn hàng: POST /b2b/core/api/ext/order/send
 * - Tra cứu đơn hàng: POST /b2b/core/api/ext/order/get-order
 * - Hoàn đơn hàng: POST /b2b/core/api/ext/refund/send
 * - Hủy đơn hàng: POST /b2b/core/api/ext/order/cancel
 * 
 * Lưu ý: Khác với Master-Sub, Direct sử dụng merchant_code thay vì master_merchant_code + sub_merchant_code
 * 
 * @package Baokim\B2B\Direct
 */

namespace Baokim\B2B\Direct;

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\HttpClient;
use Baokim\B2B\SignatureHelper;

class BaokimDirect
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
     * API Endpoints cho Direct Connection
     */
    const ENDPOINT_CREATE_ORDER = '/b2b/core/api/ext/order/send';
    const ENDPOINT_QUERY_ORDER = '/b2b/core/api/ext/order/get-order';
    const ENDPOINT_REFUND_ORDER = '/b2b/core/api/ext/refund/send';
    const ENDPOINT_CANCEL_ORDER = '/b2b/core/api/ext/order/cancel';
    
    /**
     * Payment methods
     */
    const PAYMENT_METHOD_VA = 1;           // Virtual Account
    const PAYMENT_METHOD_BNPL = 2;         // Buy Now Pay Later
    const PAYMENT_METHOD_CREDIT_CARD = 3;  // Thẻ tín dụng
    const PAYMENT_METHOD_INSTALLMENT = 4;  // Trả góp
    const PAYMENT_METHOD_ATM = 5;          // ATM nội địa
    const PAYMENT_METHOD_VNPAY_QR = 6;     // VNPay QR
    
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
     * Tạo đơn hàng mới (Direct Connection)
     * 
     * @param array $orderData Thông tin đơn hàng
     *        - mrc_order_id: (string, required) Mã đơn hàng của Merchant (max 50 ký tự)
     *        - total_amount: (int, required) Tổng tiền đơn hàng
     *        - description: (string, required) Mô tả đơn hàng (max 255 ký tự)
     *        - url_success: (string, required) URL redirect khi thanh toán thành công
     *        - url_fail: (string, required) URL redirect khi thanh toán thất bại
     *        - payment_method: (int, optional) Phương thức thanh toán (1=VA, 2=BNPL, 3=Credit Card, 4=Installment, 5=ATM, 6=VNPayQR)
     *        - items: (array, optional) Danh sách sản phẩm
     *        - customer_info: (array, optional) Thông tin khách hàng
     *        - store_code: (string, optional) Mã cửa hàng
     *        - branch_code: (string, optional) Mã chi nhánh
     *        - staff_code: (string, optional) Mã nhân viên
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
        
        // Tạo request ID
        $requestId = (string)time() . rand(100, 999);
        
        // Chuẩn bị request body - CHỈ required fields
        $requestBody = [
            'request_id' => $requestId,
            'request_time' => date('Y-m-d H:i:s'),
            'merchant_code' => Config::get('direct_merchant_code') ?: Config::get('merchant_code'),
            'mrc_order_id' => $orderData['mrc_order_id'],
            'description' => $orderData['description'],
            'total_amount' => (int)$orderData['total_amount'],
            'url_success' => isset($orderData['url_success']) 
                ? $orderData['url_success'] 
                : Config::get('url_success'),
            'url_fail' => isset($orderData['url_fail']) 
                ? $orderData['url_fail'] 
                : Config::get('url_fail'),
        ];
        
        // Thêm optional fields CHỈ KHI có giá trị (không empty)
        if (!empty($orderData['store_code'])) {
            $requestBody['store_code'] = $orderData['store_code'];
        }
        if (!empty($orderData['branch_code'])) {
            $requestBody['branch_code'] = $orderData['branch_code'];
        }
        if (!empty($orderData['staff_code'])) {
            $requestBody['staff_code'] = $orderData['staff_code'];
        }
        
        // Items
        if (isset($orderData['items']) && is_array($orderData['items'])) {
            $requestBody['items'] = $orderData['items'];
        }
        
        // Customer info - required theo Baokim
        $customerInfo = isset($orderData['customer_info']) && is_array($orderData['customer_info']) 
            ? $orderData['customer_info'] 
            : [];
        $requestBody['customer_info'] = [
            'name' => isset($customerInfo['name']) ? $customerInfo['name'] : 'NGUYEN VAN A',
            'email' => isset($customerInfo['email']) ? $customerInfo['email'] : 'test@example.com',
            'phone' => isset($customerInfo['phone']) ? $customerInfo['phone'] : '0901234567',
            'address' => isset($customerInfo['address']) ? $customerInfo['address'] : '123 Test',
            'gender' => isset($customerInfo['gender']) ? $customerInfo['gender'] : 1,
        ];
        // Thêm customer code nếu có
        if (!empty($customerInfo['code'])) {
            $requestBody['customer_info']['code'] = $customerInfo['code'];
        }
        
        // Payment method
        if (isset($orderData['payment_method'])) {
            $requestBody['payment_method'] = (string)$orderData['payment_method'];
        }
        
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
            'merchant_code' => Config::get('merchant_code'),             // required: Mã merchant
            'mrc_order_id' => $mrcOrderId,                               // required: Mã đơn hàng của Merchant
        ];
        
        return $this->sendRequest(self::ENDPOINT_QUERY_ORDER, $requestBody);
    }
    
    /**
     * Hoàn tiền đơn hàng
     * 
     * @param string $mrcOrderId Mã đơn hàng của Merchant
     * @param string $description Lý do hoàn tiền (required)
     * @param int|null $amount Số tiền hoàn (null = hoàn toàn bộ)
     * @param string|null $accountNo Số tài khoản nhận tiền hoàn (optional, required nếu gặp lỗi 116)
     * @param string|null $bankNo Mã ngân hàng nhận tiền hoàn (optional, required nếu gặp lỗi 116)
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function refundOrder($mrcOrderId, $description, $amount = null, $accountNo = null, $bankNo = null)
    {
        // Validate required fields
        if (empty($mrcOrderId)) {
            throw new \Exception("Missing required field: mrc_order_id");
        }
        if (empty($description)) {
            throw new \Exception("Missing required field: description");
        }
        
        // Chuẩn bị request body với tất cả các trường
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'merchant_code' => Config::get('merchant_code'),             // required: Mã merchant
            'mrc_order_id' => $mrcOrderId,                               // required: Mã đơn hàng của Merchant
            'description' => $description,                                // required: Nội dung/lý do hoàn tiền
            
            // === OPTIONAL FIELDS ===
            'amount' => $amount !== null ? (int)$amount : null,          // optional: Số tiền hoàn (null = hoàn toàn bộ)
            'account_no' => $accountNo !== null 
                ? $accountNo 
                : null,                                                   // optional: Số tài khoản nhận tiền hoàn (required nếu lỗi 116)
            'bank_no' => $bankNo !== null 
                ? $bankNo 
                : null,                                                   // optional: Mã ngân hàng nhận tiền hoàn (required nếu lỗi 116)
        ];
        
        // Loại bỏ các field null để tránh lỗi validation từ API
        $requestBody = array_filter($requestBody, function($value) {
            return $value !== null;
        });
        
        return $this->sendRequest(self::ENDPOINT_REFUND_ORDER, $requestBody);
    }
    
    /**
     * Hủy đơn hàng (chỉ áp dụng cho đơn chưa thanh toán)
     * 
     * @param string $mrcOrderId Mã đơn hàng của Merchant
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function cancelOrder($mrcOrderId)
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
            'merchant_code' => Config::get('merchant_code'),             // required: Mã merchant
            'mrc_order_id' => $mrcOrderId,                               // required: Mã đơn hàng của Merchant
        ];
        
        return $this->sendRequest(self::ENDPOINT_CANCEL_ORDER, $requestBody);
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
        
        // Ký request body - encode JSON một lần duy nhất
        $jsonBody = json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = SignatureHelper::sign($jsonBody);
        
        // Gửi request với JSON đã ký (dùng postRaw để không re-encode)
        $response = $this->httpClient->postRaw($endpoint, $jsonBody, [
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
     * Note: Baokim dùng merchant_code trong request_id để thống kê và gửi thông báo cập nhật SDK.
     * Vui lòng giữ nguyên format này.
     * 
     * @return string
     */
    private function generateRequestId()
    {
        return Config::get('merchant_code') . '_DIRECT_' . date('YmdHis') . '_' . uniqid();
    }
    
    /**
     * Helper: Tạo thông tin customer
     * 
     * @param string $name Tên khách hàng
     * @param string $email Email
     * @param string $phone Số điện thoại
     * @param string $address Địa chỉ (optional)
     * @param int $gender Giới tính 1=male, 2=female (optional)
     * @param string $code Mã khách hàng (optional)
     * @return array
     */
    public static function buildCustomerInfo($name, $email, $phone, $address = '', $gender = 1, $code = '')
    {
        return [
            'code' => $code,           // optional: Mã khách hàng
            'name' => $name,           // required: Tên khách hàng
            'email' => $email,         // required: Email
            'phone' => $phone,         // required: Số điện thoại
            'address' => $address,     // optional: Địa chỉ
            'gender' => $gender,       // optional: Giới tính (1=male, 2=female)
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
            'code' => $code,           // required: Mã sản phẩm
            'name' => $name,           // required: Tên sản phẩm
            'amount' => (int)$amount,  // required: Đơn giá
            'quantity' => (int)$quantity, // required: Số lượng
            'link' => $link,           // optional: Link sản phẩm
        ];
    }
}
