<?php
/**
 * Class HttpClient
 * 
 * HTTP Client sử dụng cURL thuần (không dùng Guzzle)
 * 
 * @package Baokim\B2B
 */

namespace Baokim\B2B;

class HttpClient
{
    /**
     * @var int Timeout mặc định (giây)
     */
    private $timeout = 30;
    
    /**
     * @var string Base URL cho API
     */
    private $baseUrl = '';
    
    /**
     * @var array Default headers
     */
    private $defaultHeaders = [];
    
    /**
     * @var array Response info từ cURL
     */
    private $lastResponseInfo = [];
    
    /**
     * @var string Response headers từ request cuối
     */
    private $lastResponseHeaders = '';
    
    /**
     * Constructor
     * 
     * @param string|null $baseUrl Base URL cho API
     * @param int|null $timeout Timeout (giây)
     */
    public function __construct($baseUrl = null, $timeout = null)
    {
        $this->baseUrl = $baseUrl ?: Config::get('base_url', '');
        $this->timeout = $timeout ?: Config::get('timeout', 30);
        
        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
    
    /**
     * Gửi POST request
     * 
     * @param string $endpoint Đường dẫn API (sẽ nối với base_url)
     * @param array $data Dữ liệu gửi đi
     * @param array $headers Headers bổ sung
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null, 'http_code' => int]
     */
    public function post($endpoint, array $data = [], array $headers = [])
    {
        $url = $this->buildUrl($endpoint);
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $allHeaders = array_merge($this->defaultHeaders, $headers);
        $headerList = $this->formatHeaders($allHeaders);
        
        return $this->sendRequest($url, 'POST', $jsonData, $headerList);
    }
    
    /**
     * Gửi GET request
     * 
     * @param string $endpoint Đường dẫn API
     * @param array $params Query parameters
     * @param array $headers Headers bổ sung
     * @return array
     */
    public function get($endpoint, array $params = [], array $headers = [])
    {
        $url = $this->buildUrl($endpoint);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $allHeaders = array_merge($this->defaultHeaders, $headers);
        $headerList = $this->formatHeaders($allHeaders);
        
        return $this->sendRequest($url, 'GET', null, $headerList);
    }
    
    /**
     * Gửi request với cURL
     * 
     * @param string $url Full URL
     * @param string $method HTTP method
     * @param string|null $body Request body
     * @param array $headers Headers list
     * @return array
     */
    private function sendRequest($url, $method, $body = null, array $headers = [])
    {
        // Log request
        Logger::logRequest($method, $url, $headers, $body ? json_decode($body, true) : null);
        
        $startTime = microtime(true);
        
        $ch = curl_init();
        
        // Cấu hình cURL options
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER => true,  // Để lấy response headers
        ];
        
        // Thiết lập method và body
        switch (strtoupper($method)) {
            case 'POST':
                $options[CURLOPT_POST] = true;
                if ($body !== null) {
                    $options[CURLOPT_POSTFIELDS] = $body;
                }
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if ($body !== null) {
                    $options[CURLOPT_POSTFIELDS] = $body;
                }
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
            case 'GET':
            default:
                $options[CURLOPT_HTTPGET] = true;
                break;
        }
        
        curl_setopt_array($ch, $options);
        
        // Thực hiện request
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $this->lastResponseInfo = curl_getinfo($ch);
        
        $duration = round(microtime(true) - $startTime, 3);
        
        curl_close($ch);
        
        // Xử lý lỗi cURL
        if ($errno !== 0) {
            Logger::error("cURL Error [{$errno}]: {$error}", ['url' => $url]);
            return [
                'success' => false,
                'data' => null,
                'error' => "cURL Error [{$errno}]: {$error}",
                'http_code' => 0,
            ];
        }
        
        // Tách headers và body từ response
        $headerSize = $this->lastResponseInfo['header_size'];
        $this->lastResponseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        
        $httpCode = $this->lastResponseInfo['http_code'];
        
        // Parse JSON response
        $responseData = json_decode($responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                'http_code' => $httpCode,
                'raw_response' => $responseBody,
            ];
        }
        
        // Kiểm tra HTTP status code
        $success = ($httpCode >= 200 && $httpCode < 300);
        
        // Log response
        Logger::logResponse($httpCode, $responseData, $duration);
        
        return [
            'success' => $success,
            'data' => $responseData,
            'error' => $success ? null : $this->extractErrorMessage($responseData, $httpCode),
            'http_code' => $httpCode,
        ];
    }
    
    /**
     * Build full URL từ base URL và endpoint
     * 
     * @param string $endpoint
     * @return string
     */
    private function buildUrl($endpoint)
    {
        // Nếu endpoint đã là full URL
        if (strpos($endpoint, 'http://') === 0 || strpos($endpoint, 'https://') === 0) {
            return $endpoint;
        }
        
        $baseUrl = rtrim($this->baseUrl, '/');
        $endpoint = ltrim($endpoint, '/');
        
        return $baseUrl . '/' . $endpoint;
    }
    
    /**
     * Format headers array thành cURL header list
     * 
     * @param array $headers Key-value array
     * @return array List of "Key: Value" strings
     */
    private function formatHeaders(array $headers)
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "{$key}: {$value}";
        }
        return $formatted;
    }
    
    /**
     * Trích xuất error message từ response
     * 
     * @param array|null $responseData
     * @param int $httpCode
     * @return string
     */
    private function extractErrorMessage($responseData, $httpCode)
    {
        if (isset($responseData['message'])) {
            return $responseData['message'];
        }
        
        if (isset($responseData['error'])) {
            return is_string($responseData['error']) 
                ? $responseData['error'] 
                : json_encode($responseData['error']);
        }
        
        return "HTTP Error: {$httpCode}";
    }
    
    /**
     * Lấy thông tin response cuối cùng
     * 
     * @return array
     */
    public function getLastResponseInfo()
    {
        return $this->lastResponseInfo;
    }
    
    /**
     * Lấy response headers cuối cùng
     * 
     * @return string
     */
    public function getLastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }
    
    /**
     * Parse header Signature từ response headers
     * 
     * @return string|null
     */
    public function getSignatureFromLastResponse()
    {
        if (preg_match('/^Signature:\s*(.+)$/mi', $this->lastResponseHeaders, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
    
    /**
     * Set base URL
     * 
     * @param string $baseUrl
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }
    
    /**
     * Set timeout
     * 
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }
    
    /**
     * Thêm default header
     * 
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addDefaultHeader($key, $value)
    {
        $this->defaultHeaders[$key] = $value;
        return $this;
    }
}
