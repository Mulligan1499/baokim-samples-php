<?php
/**
 * Class BaokimVA
 * 
 * Quản lý Virtual Account qua Baokim B2B API (Host-to-Host Connection)
 * 
 * Kết nối Host-to-Host dùng master_merchant_code và sub_merchant_code
 * 
 * Bao gồm:
 * - Tạo VA: POST /b2b/core/api/ext/mm/bank-transfer/create
 * - Cập nhật VA: POST /b2b/core/api/ext/mm/bank-transfer/update
 * - Tra cứu giao dịch: POST /b2b/core/api/ext/mm/bank-transfer/detail
 * 
 * @package Baokim\B2B\HostToHost
 */

namespace Baokim\B2B\HostToHost;

use Baokim\B2B\Config;
use Baokim\B2B\BaokimAuth;
use Baokim\B2B\HttpClient;
use Baokim\B2B\SignatureHelper;

class BaokimVA
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
    const ENDPOINT_CREATE_VA = '/b2b/core/api/ext/mm/bank-transfer/create';
    const ENDPOINT_UPDATE_VA = '/b2b/core/api/ext/mm/bank-transfer/update';
    const ENDPOINT_QUERY_TRANSACTION = '/b2b/core/api/ext/mm/bank-transfer/detail';
    const ENDPOINT_REFUND = '/b2b/core/api/ext/mm/refund/send';
    
    /**
     * Loại VA
     */
    const ACC_TYPE_DYNAMIC = 1;  // VA động - mỗi đơn hàng 1 VA duy nhất
    const ACC_TYPE_STATIC = 2;   // VA tĩnh - 1 VA dùng cho nhiều giao dịch
    
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
     * Tạo Virtual Account mới
     * 
     * @param array $vaData Thông tin VA
     *        - acc_name: (string, required) Tên chủ tài khoản VA
     *        - acc_type: (int, required) Loại VA: 1=Dynamic, 2=Static
     *        - mrc_order_id: (string, required) Mã đơn hàng của Merchant
     *        - collect_amount_min: (int, optional) Số tiền thu tối thiểu
     *        - collect_amount_max: (int, optional) Số tiền thu tối đa
     *        - expire_date: (string, optional) Ngày hết hạn (YYYY-MM-DD H:i:s), bắt buộc nếu acc_type=2
     *        - description: (string, optional) Mô tả
     * @return array Response từ Baokim (bao gồm acc_no, qr_string, qr_path)
     * @throws \Exception
     */
    public function createVA(array $vaData)
    {
        // Validate required fields
        $requiredFields = ['acc_name', 'acc_type', 'mrc_order_id'];
        foreach ($requiredFields as $field) {
            if (!isset($vaData[$field]) || $vaData[$field] === '' || $vaData[$field] === null) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
        
        // Validate acc_type
        if (!in_array($vaData['acc_type'], [self::ACC_TYPE_DYNAMIC, self::ACC_TYPE_STATIC])) {
            throw new \Exception("Invalid acc_type. Must be 1 (Dynamic) or 2 (Static)");
        }
        
        // Static VA phải có expire_date
        if ($vaData['acc_type'] == self::ACC_TYPE_STATIC && !isset($vaData['expire_date'])) {
            throw new \Exception("expire_date is required for Static VA (acc_type=2)");
        }
        
        // Chuẩn bị request body (giống code gốc - KHÔNG dùng array_filter)
        $requestBody = [
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'master_merchant_code' => Config::get('master_merchant_code'),
            'sub_merchant_code' => Config::get('sub_merchant_code'),
            'acc_name' => $vaData['acc_name'],
            'acc_type' => (int)$vaData['acc_type'],
            'mrc_order_id' => $vaData['mrc_order_id'],
        ];
        
        // Thêm các trường optional (cách gốc)
        if (isset($vaData['collect_amount_min'])) {
            $requestBody['collect_amount_min'] = (int)$vaData['collect_amount_min'];
        }
        if (isset($vaData['collect_amount_max'])) {
            $requestBody['collect_amount_max'] = (int)$vaData['collect_amount_max'];
        }
        if (isset($vaData['expire_date'])) {
            $requestBody['expire_date'] = $vaData['expire_date'];
        }
        if (isset($vaData['description'])) {
            $requestBody['description'] = $vaData['description'];
        }
        
        return $this->sendRequest(self::ENDPOINT_CREATE_VA, $requestBody);
    }
    
    /**
     * Cập nhật thông tin VA
     * 
     * @param string $accNo Số VA cần cập nhật
     * @param array $updateData Dữ liệu cập nhật
     *        - acc_name: (string, optional) Tên chủ tài khoản VA
     *        - collect_amount_min: (int, optional) Số tiền thu tối thiểu
     *        - collect_amount_max: (int, optional) Số tiền thu tối đa
     *        - expire_date: (string, optional) Ngày hết hạn mới
     *        - status: (int, optional) Trạng thái VA (1=Active, 0=Inactive)
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function updateVA($accNo, array $updateData)
    {
        // Validate required field
        if (empty($accNo)) {
            throw new \Exception("Missing required field: acc_no");
        }
        
        // Chuẩn bị request body với tất cả các trường
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'master_merchant_code' => Config::get('master_merchant_code'),
            'sub_merchant_code' => Config::get('sub_merchant_code'),
            'acc_no' => $accNo,                                              // required: Số VA cần cập nhật
            
            // === OPTIONAL FIELDS ===
            'acc_name' => isset($updateData['acc_name']) 
                ? $updateData['acc_name'] 
                : null,                                                       // optional: Tên chủ tài khoản VA
            'collect_amount_min' => isset($updateData['collect_amount_min']) 
                ? (int)$updateData['collect_amount_min'] 
                : null,                                                       // optional: Số tiền thu tối thiểu
            'collect_amount_max' => isset($updateData['collect_amount_max']) 
                ? (int)$updateData['collect_amount_max'] 
                : null,                                                       // optional: Số tiền thu tối đa
            'expire_date' => isset($updateData['expire_date']) 
                ? $updateData['expire_date'] 
                : null,                                                       // optional: Ngày hết hạn mới
            'status' => isset($updateData['status']) 
                ? (int)$updateData['status'] 
                : null,                                                       // optional: Trạng thái VA (1=Active, 0=Inactive)
        ];
        
        // Loại bỏ các field null để tránh lỗi validation từ API
        $requestBody = array_filter($requestBody, function($value) {
            return $value !== null;
        });
        
        return $this->sendRequest(self::ENDPOINT_UPDATE_VA, $requestBody);
    }
    
    /**
     * Tra cứu giao dịch theo VA hoặc mrc_order_id
     * 
     * @param array $queryData Điều kiện tra cứu
     *        - acc_no: (string, optional) Số VA
     *        - mrc_order_id: (string, optional) Mã đơn hàng
     *        - from_date: (string, optional) Từ ngày (YYYY-MM-DD)
     *        - to_date: (string, optional) Đến ngày (YYYY-MM-DD)
     * @return array Response từ Baokim
     * @throws \Exception
     */
    public function queryTransaction(array $queryData)
    {
        // Chuẩn bị request body với tất cả các trường
        $requestBody = [
            // === REQUIRED FIELDS ===
            'request_id' => $this->generateRequestId(),
            'request_time' => date('Y-m-d H:i:s'),
            'master_merchant_code' => Config::get('master_merchant_code'),
            'sub_merchant_code' => Config::get('sub_merchant_code'),
            
            // === OPTIONAL FIELDS ===
            'acc_no' => isset($queryData['acc_no']) 
                ? $queryData['acc_no'] 
                : null,                                                       // optional: Số VA
            'start_date' => isset($queryData['start_date']) 
                ? $queryData['start_date'] 
                : null,                                                       // optional: Từ ngày (YYYY-MM-DD)
            'end_date' => isset($queryData['end_date']) 
                ? $queryData['end_date'] 
                : null,                                                       // optional: Đến ngày (YYYY-MM-DD)
        ];
        
        // Loại bỏ các field null để tránh lỗi validation từ API
        $requestBody = array_filter($requestBody, function($value) {
            return $value !== null;
        });
        
        return $this->sendRequest(self::ENDPOINT_QUERY_TRANSACTION, $requestBody);
    }
    
    /**
     * Tạo Dynamic VA nhanh
     * 
     * @param string $accName Tên chủ VA
     * @param string $mrcOrderId Mã đơn hàng
     * @param int $amount Số tiền cần thu
     * @param string $description Mô tả (optional)
     * @return array
     * @throws \Exception
     */
    public function createDynamicVA($accName, $mrcOrderId, $amount, $description = '')
    {
        $vaData = [
            'acc_name' => $accName,
            'acc_type' => self::ACC_TYPE_DYNAMIC,
            'mrc_order_id' => $mrcOrderId,
            'collect_amount_min' => $amount,
            'collect_amount_max' => $amount,
        ];
        
        // Chỉ thêm description nếu có giá trị
        if (!empty($description)) {
            $vaData['description'] = $description;
        }
        
        return $this->createVA($vaData);
    }
    
    /**
     * Tạo Static VA nhanh
     * 
     * @param string $accName Tên chủ VA
     * @param string $mrcOrderId Mã định danh khách hàng
     * @param string $expireDate Ngày hết hạn (YYYY-MM-DD H:i:s)
     * @param int|null $minAmount Số tiền tối thiểu (optional)
     * @param int|null $maxAmount Số tiền tối đa (optional)
     * @return array
     * @throws \Exception
     */
    public function createStaticVA($accName, $mrcOrderId, $expireDate, $minAmount = null, $maxAmount = null)
    {
        $vaData = [
            'acc_name' => $accName,
            'acc_type' => self::ACC_TYPE_STATIC,
            'mrc_order_id' => $mrcOrderId,
            'expire_date' => $expireDate,
        ];
        
        if ($minAmount !== null) {
            $vaData['collect_amount_min'] = $minAmount;
        }
        if ($maxAmount !== null) {
            $vaData['collect_amount_max'] = $maxAmount;
        }
        
        return $this->createVA($vaData);
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
     * @return string
     */
    private function generateRequestId()
    {
        return Config::get('merchant_code') . '_VA_' . date('YmdHis') . '_' . uniqid();
    }
}
