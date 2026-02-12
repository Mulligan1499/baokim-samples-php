<?php
/**
 * Class ErrorCode
 * 
 * Mapping các mã lỗi của Baokim B2B API
 * 
 * @package Baokim\B2B
 */

namespace Baokim\B2B;

class ErrorCode
{
    /**
     * Success codes
     */
    const SUCCESS = 0;              // Thành công
    const PROCESSING = 100;         // Đang xử lý (Success pending)
    const SUCCESS_REDIRECT = 101;   // Thành công - Cần redirect trình duyệt
    
    /**
     * Error codes
     */
    const PROVIDER_ERROR = 102;     // Lỗi từ nhà cung cấp dịch vụ
    const SIGNATURE_INVALID = 104;  // Chữ ký không hợp lệ hoặc dữ liệu sai định dạng
    const AUTH_FAILED = 111;        // Xác thực thất bại
    const DATA_INVALID = 422;       // Dữ liệu không hợp lệ
    const DUPLICATE_ORDER = 707;    // Mã đơn hàng đã tồn tại
    
    /**
     * HTTP Status codes
     */
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SERVER_ERROR = 500;
    
    /**
     * Mapping mã lỗi sang mô tả tiếng Việt
     * 
     * @var array
     */
    private static $messages = [
        // Success
        self::SUCCESS => 'Thành công',
        self::PROCESSING => 'Đang xử lý',
        self::SUCCESS_REDIRECT => 'Thành công - Vui lòng redirect trình duyệt khách hàng',
        
        // Errors
        self::PROVIDER_ERROR => 'Lỗi từ nhà cung cấp dịch vụ thanh toán',
        self::SIGNATURE_INVALID => 'Chữ ký không hợp lệ hoặc dữ liệu không đúng định dạng',
        self::AUTH_FAILED => 'Xác thực thất bại. Vui lòng kiểm tra merchant_code, client_id, client_secret',
        self::DATA_INVALID => 'Dữ liệu đầu vào không hợp lệ',
        self::DUPLICATE_ORDER => 'Mã đơn hàng đã tồn tại trong hệ thống',
        
        // HTTP errors
        self::HTTP_UNAUTHORIZED => 'Token không hợp lệ hoặc đã hết hạn',
        self::HTTP_FORBIDDEN => 'Không có quyền truy cập',
        self::HTTP_NOT_FOUND => 'API endpoint không tồn tại',
        self::HTTP_SERVER_ERROR => 'Lỗi server Baokim',
    ];
    
    /**
     * Mapping mã lỗi sang mô tả tiếng Anh
     * 
     * @var array
     */
    private static $messagesEn = [
        self::SUCCESS => 'Success',
        self::PROCESSING => 'Processing',
        self::SUCCESS_REDIRECT => 'Success - Please redirect customer browser',
        self::PROVIDER_ERROR => 'Payment provider error',
        self::SIGNATURE_INVALID => 'Invalid signature or malformed data',
        self::AUTH_FAILED => 'Authentication failed. Please check merchant_code, client_id, client_secret',
        self::DATA_INVALID => 'Invalid input data',
        self::DUPLICATE_ORDER => 'Order ID already exists',
        self::HTTP_UNAUTHORIZED => 'Invalid or expired token',
        self::HTTP_FORBIDDEN => 'Access denied',
        self::HTTP_NOT_FOUND => 'API endpoint not found',
        self::HTTP_SERVER_ERROR => 'Baokim server error',
    ];
    
    /**
     * Lấy mô tả lỗi theo mã (tiếng Việt)
     * 
     * @param int $code Mã lỗi
     * @param string|null $default Mô tả mặc định nếu không tìm thấy
     * @return string
     */
    public static function getMessage($code, $default = null)
    {
        if (isset(self::$messages[$code])) {
            return self::$messages[$code];
        }
        
        return $default ?: "Mã lỗi không xác định: {$code}";
    }
    
    /**
     * Lấy mô tả lỗi theo mã (tiếng Anh)
     * 
     * @param int $code Mã lỗi
     * @param string|null $default Mô tả mặc định nếu không tìm thấy
     * @return string
     */
    public static function getMessageEn($code, $default = null)
    {
        if (isset(self::$messagesEn[$code])) {
            return self::$messagesEn[$code];
        }
        
        return $default ?: "Unknown error code: {$code}";
    }
    
    /**
     * Kiểm tra có phải mã thành công không
     * 
     * @param int $code
     * @return bool
     */
    public static function isSuccess($code)
    {
        return in_array($code, [self::SUCCESS, self::PROCESSING, self::SUCCESS_REDIRECT]);
    }
    
    /**
     * Kiểm tra có phải lỗi xác thực không
     * 
     * @param int $code
     * @return bool
     */
    public static function isAuthError($code)
    {
        return in_array($code, [self::AUTH_FAILED, self::HTTP_UNAUTHORIZED, self::HTTP_FORBIDDEN]);
    }
    
    /**
     * Kiểm tra có phải lỗi dữ liệu không
     * 
     * @param int $code
     * @return bool
     */
    public static function isValidationError($code)
    {
        return in_array($code, [self::DATA_INVALID, self::SIGNATURE_INVALID, self::DUPLICATE_ORDER]);
    }
    
    /**
     * Lấy tất cả mã lỗi
     * 
     * @return array [code => message]
     */
    public static function getAllCodes()
    {
        return self::$messages;
    }
    
    /**
     * Tạo Exception từ mã lỗi
     * 
     * @param int $code
     * @param string|null $customMessage Thông báo custom (optional)
     * @return \Exception
     */
    public static function toException($code, $customMessage = null)
    {
        $message = $customMessage ?: self::getMessage($code);
        return new \Exception("[{$code}] {$message}", $code);
    }
}
