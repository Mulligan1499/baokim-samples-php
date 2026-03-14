<?php
/**
 * Class BaokimMerchantVA
 * 
 * Quản lý Virtual Account qua Baokim B2B API (Merchant Hosted / Direct Connection)
 * 
 * Kết nối Merchant Hosted dùng merchant_code (không cần master/sub merchant)
 * 
 * Bao gồm:
 * - Tạo VA: POST /b2b/core/api/merchant-hosted/bank-transfer/create
 * - Cập nhật VA: POST /b2b/core/api/merchant-hosted/bank-transfer/update
 * - Tra cứu chi tiết VA: POST /b2b/core/api/merchant-hosted/bank-transfer/detail
 * 
 * @package Baokim\B2B\MerchantHosted
 */

namespace Baokim\B2B\MerchantHosted;

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\HttpClient;
use Baokim\B2B\SignatureHelper;

class BaokimMerchantVA
{
    /**
     * @var HttpClient
     */
    private $httpClient;
    
    /**
     * @var string|null
     */
    private $token;
    
    /**
     * API Endpoints (Merchant Hosted)
     */
    const ENDPOINT_CREATE_VA = '/b2b/core/api/merchant-hosted/bank-transfer/create';
    const ENDPOINT_UPDATE_VA = '/b2b/core/api/merchant-hosted/bank-transfer/update';
    const ENDPOINT_DETAIL_VA = '/b2b/core/api/merchant-hosted/bank-transfer/detail';
    
    /**
     * Loại VA
     */
    const ACC_TYPE_DYNAMIC = 1;  // VA động - mỗi đơn hàng 1 VA duy nhất
    const ACC_TYPE_STATIC = 2;   // VA tĩnh - 1 VA dùng cho nhiều giao dịch
    
    /**
     * Constructor
     * 
     * @param string|null $token Access token string (Direct connection). Nếu null, SDK tự gọi API lấy token mới.
     * @param HttpClient|null $httpClient
     */
    public function __construct($token = null, HttpClient $httpClient = null)
    {
        $this->token = $token;
        $this->httpClient = $httpClient ?: new HttpClient();
    }
    
    /**
     * Tạo Virtual Account mới (Merchant Hosted)
     * 
     * @param array $vaData Thông tin VA
     *        - acc_name: (string, required) Tên chủ tài khoản VA
     *        - acc_type: (int, required) Loại VA: 1=Dynamic, 2=Static
     *        - mrc_order_id: (string, required) Mã đơn hàng của Merchant (max 25 ký tự)
     *        - collect_amount_max: (int, required) Số tiền thu tối đa (tối thiểu 2000)
     *        - collect_amount_min: (int, optional) Số tiền thu tối thiểu (tối thiểu 2000, bắt buộc nếu acc_type=1)
     *        - store_code: (string, optional) Mã cửa hàng
     *        - staff_code: (string, optional) Mã nhân viên
     *        - bank_code: (string, optional) Mã ngân hàng
     *        - expire_date: (string, optional) Ngày hết hạn (Y-m-d H:i:s), bắt buộc nếu acc_type=2
     *        - memo: (string, optional) Ghi chú (max 255 ký tự)
     * @return array Response từ Baokim (bao gồm acc_no, qr_string, qr_path)
     * @throws \Exception
     */
    public function createVA(array $vaData)
    {
        // Validate required fields
        $requiredFields = ['acc_name', 'acc_type', 'mrc_order_id', 'collect_amount_max'];
        foreach ($requiredFields as $field) {
            if (!isset($vaData[$field]) || $vaData[$field] === '' || $vaData[$field] === null) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Validate acc_type
        if (!in_array($vaData['acc_type'], [self::ACC_TYPE_DYNAMIC, self::ACC_TYPE_STATIC])) {
            throw new \Exception("Invalid acc_type. Must be 1 (Dynamic) or 2 (Static)");
        }
        
        // Dynamic VA phải có collect_amount_min và phải bằng collect_amount_max
        if ($vaData['acc_type'] == self::ACC_TYPE_DYNAMIC) {
            if (!isset($vaData['collect_amount_min'])) {
                throw new \Exception("collect_amount_min is required for Dynamic VA (acc_type=1)");
            }
            if ((int)$vaData['collect_amount_min'] !== (int)$vaData['collect_amount_max']) {
                throw new \Exception("collect_amount_min must equal collect_amount_max for Dynamic VA (acc_type=1)");
            }
        }
        
        // Static VA phải có expire_date
        if ($vaData['acc_type'] == self::ACC_TYPE_STATIC && !isset($vaData['expire_date'])) {
            throw new \Exception("expire_date is required for Static VA (acc_type=2)");
        }
        
        // Chuẩn bị request body
        $requestBody = [
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'merchant_code' => Config::get('direct_merchant_code') ?: Config::get('merchant_code'),
            'acc_name' => $vaData['acc_name'],
            'acc_type' => (int)$vaData['acc_type'],
            'mrc_order_id' => $vaData['mrc_order_id'],
            'collect_amount_max' => (int)$vaData['collect_amount_max'],
        ];
        
        // Thêm các trường optional
        if (isset($vaData['collect_amount_min'])) {
            $requestBody['collect_amount_min'] = (int)$vaData['collect_amount_min'];
        }
        if (isset($vaData['store_code']) && !empty($vaData['store_code'])) {
            $requestBody['store_code'] = $vaData['store_code'];
        }
        if (isset($vaData['staff_code']) && !empty($vaData['staff_code'])) {
            $requestBody['staff_code'] = $vaData['staff_code'];
        }
        if (isset($vaData['bank_code']) && !empty($vaData['bank_code'])) {
            $requestBody['bank_code'] = $vaData['bank_code'];
        }
        if (isset($vaData['expire_date'])) {
            $requestBody['expire_date'] = $vaData['expire_date'];
        }
        if (isset($vaData['memo']) && !empty($vaData['memo'])) {
            $requestBody['memo'] = $vaData['memo'];
        }
        
        return $this->sendRequest(self::ENDPOINT_CREATE_VA, $requestBody);
    }
    
    /**
     * Cập nhật thông tin VA (Merchant Hosted)
     * 
     * @param string $mrcOrderId Mã đơn hàng của Merchant (required)
     * @param array $updateData Dữ liệu cập nhật
     *        - acc_name: (string, optional) Tên chủ tài khoản VA
     *        - collect_amount_min: (int, optional) Số tiền thu tối thiểu
     *        - collect_amount_max: (int, optional) Số tiền thu tối đa
     *        - expire_date: (string, optional) Ngày hết hạn mới (Y-m-d H:i:s)
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function updateVA($mrcOrderId, array $updateData)
    {
        // Validate required field
        if (empty($mrcOrderId)) {
            throw new \Exception("Missing required field: mrc_order_id");
        }
        
        // Chuẩn bị request body
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'merchant_code' => Config::get('direct_merchant_code') ?: Config::get('merchant_code'),
            'mrc_order_id' => $mrcOrderId,
            
            // === OPTIONAL FIELDS ===
            'acc_name' => isset($updateData['acc_name']) 
                ? $updateData['acc_name'] 
                : null,
            'collect_amount_min' => isset($updateData['collect_amount_min']) 
                ? (int)$updateData['collect_amount_min'] 
                : null,
            'collect_amount_max' => isset($updateData['collect_amount_max']) 
                ? (int)$updateData['collect_amount_max'] 
                : null,
            'expire_date' => isset($updateData['expire_date']) 
                ? $updateData['expire_date'] 
                : null,
        ];
        
        // Loại bỏ các field null
        $requestBody = array_filter($requestBody, function($value) {
            return $value !== null;
        });
        
        return $this->sendRequest(self::ENDPOINT_UPDATE_VA, $requestBody);
    }
    
    /**
     * Tra cứu chi tiết VA và giao dịch (Merchant Hosted)
     * 
     * @param string $accNo Số VA cần tra cứu (required)
     * @param array $queryData Điều kiện tra cứu bổ sung
     *        - start_date: (string, optional) Từ ngày (Y-m-d H:i:s)
     *        - end_date: (string, optional) Đến ngày (Y-m-d H:i:s)
     *        - current_page: (int, optional) Trang hiện tại (mặc định 1)
     *        - per_page: (int, optional) Số bản ghi mỗi trang (mặc định 20)
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function detailVA($accNo, array $queryData = [])
    {
        // Validate required field
        if (empty($accNo)) {
            throw new \Exception("Missing required field: acc_no");
        }
        
        // Chuẩn bị request body
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'merchant_code' => Config::get('direct_merchant_code') ?: Config::get('merchant_code'),
            'acc_no' => $accNo,
            
            // === OPTIONAL FIELDS ===
            'start_date' => isset($queryData['start_date']) 
                ? $queryData['start_date'] 
                : null,
            'end_date' => isset($queryData['end_date']) 
                ? $queryData['end_date'] 
                : null,
            'current_page' => isset($queryData['current_page']) 
                ? (int)$queryData['current_page'] 
                : null,
            'per_page' => isset($queryData['per_page']) 
                ? (int)$queryData['per_page'] 
                : null,
        ];
        
        // Loại bỏ các field null
        $requestBody = array_filter($requestBody, function($value) {
            return $value !== null;
        });
        
        return $this->sendRequest(self::ENDPOINT_DETAIL_VA, $requestBody);
    }
    
    /**
     * Tạo Dynamic VA nhanh (Merchant Hosted)
     * 
     * @param string $accName Tên chủ VA
     * @param string $mrcOrderId Mã đơn hàng
     * @param int $amount Số tiền cần thu
     * @param string $memo Ghi chú (optional)
     * @return array
     * @throws \Exception
     */
    public function createDynamicVA($accName, $mrcOrderId, $amount, $memo = '')
    {
        $vaData = [
            'acc_name' => $accName,
            'acc_type' => self::ACC_TYPE_DYNAMIC,
            'mrc_order_id' => $mrcOrderId,
            'collect_amount_min' => $amount,
            'collect_amount_max' => $amount,
        ];
        
        if (!empty($memo)) {
            $vaData['memo'] = $memo;
        }
        
        return $this->createVA($vaData);
    }
    
    /**
     * Tạo Static VA nhanh (Merchant Hosted)
     * 
     * @param string $accName Tên chủ VA
     * @param string $mrcOrderId Mã định danh khách hàng
     * @param string $expireDate Ngày hết hạn (Y-m-d H:i:s)
     * @param int $collectAmountMax Số tiền thu tối đa (required)
     * @param int|null $collectAmountMin Số tiền tối thiểu (optional)
     * @return array
     * @throws \Exception
     */
    public function createStaticVA($accName, $mrcOrderId, $expireDate, $collectAmountMax, $collectAmountMin = null)
    {
        $vaData = [
            'acc_name' => $accName,
            'acc_type' => self::ACC_TYPE_STATIC,
            'mrc_order_id' => $mrcOrderId,
            'expire_date' => $expireDate,
            'collect_amount_max' => $collectAmountMax,
        ];
        
        if ($collectAmountMin !== null) {
            $vaData['collect_amount_min'] = $collectAmountMin;
        }
        
        return $this->createVA($vaData);
    }
    
    /**
     * Gửi request tới Baokim API (Merchant Hosted dùng Direct Connection auth)
     * 
     * @param string $endpoint
     * @param array $requestBody
     * @return array
     * @throws \Exception
     */
    private function sendRequest($endpoint, array $requestBody)
    {
        // Lấy access token (Direct connection)
        if (!$this->token) {
            $auth = BaokimAuth::forDirectConnection();
            $this->token = $auth->getToken();
        }
        $authHeader = "Bearer {$this->token}";
        
        // Ký request body
        $jsonBody = json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = SignatureHelper::sign($jsonBody);
        
        // Gửi request
        $response = $this->httpClient->postRaw($endpoint, $jsonBody, [
            'Authorization' => $authHeader,
            'Signature' => $signature,
        ]);
        
        // Xử lý response
        if (!$response['success']) {
            throw new \Exception('API request failed: ' . $response['error']);
        }
        
        $data = $response['data'];
        
        // Kiểm tra response code (0, 100, 200 đều là thành công)
        $code = isset($data['code']) ? $data['code'] : null;
        
        return [
            'success' => $code === 0 || $code === 100 || $code === 200,
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
        $merchantCode = Config::get('direct_merchant_code') ?: Config::get('merchant_code');
        return $merchantCode . '_MH_VA_' . date('YmdHis') . '_' . uniqid();
    }
}
