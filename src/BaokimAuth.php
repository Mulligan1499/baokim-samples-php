<?php
/**
 * Class BaokimAuth
 * 
 * Xác thực OAuth2 với Baokim B2B API
 * 
 * API: POST /b2b/auth-service/api/oauth/get-token
 * 
 * @package Baokim\B2B
 */

namespace Baokim\B2B;

class BaokimAuth
{
    /**
     * @var HttpClient
     */
    private $httpClient;
    
    /**
     * @var string|null Access token đã lấy
     */
    private $accessToken = null;
    
    /**
     * @var int|null Thời điểm token hết hạn (Unix timestamp)
     */
    private $tokenExpiredAt = null;
    
    /**
     * Constructor
     * 
     * @param HttpClient|null $httpClient
     */
    public function __construct(HttpClient $httpClient = null)
    {
        $this->httpClient = $httpClient ?: new HttpClient();
    }
    
    /**
     * Lấy Access Token từ Baokim
     * 
     * Token sẽ được cache trong instance, nếu chưa hết hạn sẽ trả về token cũ
     * 
     * @param bool $forceRefresh Bắt buộc lấy token mới
     * @return string Access token
     * @throws \Exception Nếu xác thực thất bại
     */
    public function getToken($forceRefresh = false)
    {
        // Kiểm tra token còn hiệu lực không
        if (!$forceRefresh && $this->isTokenValid()) {
            return $this->accessToken;
        }
        
        // Lấy token mới
        $tokenData = $this->requestToken();
        
        $this->accessToken = $tokenData['access_token'];
        
        // Parse thời gian hết hạn
        if (isset($tokenData['expired_at'])) {
            $this->tokenExpiredAt = strtotime($tokenData['expired_at']);
        } elseif (isset($tokenData['expires_in'])) {
            $this->tokenExpiredAt = time() + (int)$tokenData['expires_in'];
        } else {
            // Mặc định 1 giờ nếu không có thông tin
            $this->tokenExpiredAt = time() + 3600;
        }
        
        return $this->accessToken;
    }
    
    /**
     * Gọi API lấy token
     * 
     * @return array Token data từ API
     * @throws \Exception
     */
    private function requestToken()
    {
        $endpoint = '/b2b/auth-service/api/oauth/get-token';
        
        // Chuẩn bị request body
        $requestBody = [
            'merchant_code' => Config::get('master_merchant_code'),
            'client_id' => Config::get('client_id'),
            'client_secret' => Config::get('client_secret'),
        ];
        
        // Ký request body
        $jsonBody = json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = SignatureHelper::sign($jsonBody);
        
        // Gửi request với signature header
        $response = $this->httpClient->post($endpoint, $requestBody, [
            'Signature' => $signature,
        ]);
        // Xử lý response
        if (!$response['success']) {
            throw new \Exception('Authentication failed: ' . $response['error']);
        }
        
        $data = $response['data'];
        
        // Kiểm tra response code (0 hoặc 100 đều là thành công)
        $code = isset($data['code']) ? $data['code'] : null;
        if ($code !== 0 && $code !== 100) {
            $errorCode = $code !== null ? $code : 'unknown';
            $errorMessage = isset($data['message']) ? $data['message'] : 'Unknown error';
            throw new \Exception("Authentication failed [{$errorCode}]: {$errorMessage}");
        }
        
        // Trả về data chứa token
        if (!isset($data['data']['access_token'])) {
            throw new \Exception('Invalid response: access_token not found');
        }
        
        return $data['data'];
    }
    
    /**
     * Kiểm tra token hiện tại còn hợp lệ không
     * 
     * @return bool
     */
    public function isTokenValid()
    {
        if ($this->accessToken === null) {
            return false;
        }
        
        if ($this->tokenExpiredAt === null) {
            return false;
        }
        
        // Trừ 60 giây để tránh edge case token hết hạn giữa chừng
        return time() < ($this->tokenExpiredAt - 60);
    }
    
    /**
     * Lấy thông tin token hiện tại
     * 
     * @return array|null
     */
    public function getTokenInfo()
    {
        if ($this->accessToken === null) {
            return null;
        }
        
        return [
            'access_token' => $this->accessToken,
            'expired_at' => $this->tokenExpiredAt,
            'is_valid' => $this->isTokenValid(),
            'expires_in' => $this->tokenExpiredAt - time(),
        ];
    }
    
    /**
     * Xóa token đã cache
     * 
     * @return void
     */
    public function clearToken()
    {
        $this->accessToken = null;
        $this->tokenExpiredAt = null;
    }
    
    /**
     * Tạo Authorization header value
     * 
     * @return string "Bearer {token}"
     * @throws \Exception
     */
    public function getAuthorizationHeader()
    {
        $token = $this->getToken();
        return "Bearer {$token}";
    }
}
