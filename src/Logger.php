<?php
/**
 * Class Logger
 * 
 * Ghi log request/response cho Baokim B2B API
 * 
 * @package Baokim\B2B
 */

namespace Baokim\B2B;

class Logger
{
    /**
     * @var string Đường dẫn thư mục logs
     */
    private static $logDir = null;
    
    /**
     * @var bool Bật/tắt logging
     */
    private static $enabled = true;
    
    /**
     * Khởi tạo thư mục logs
     * 
     * @param string|null $logDir
     */
    public static function init($logDir = null)
    {
        if ($logDir === null) {
            self::$logDir = dirname(__DIR__) . '/logs';
        } else {
            self::$logDir = $logDir;
        }
        
        // Tạo thư mục nếu chưa có
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    /**
     * Bật/tắt logging
     * 
     * @param bool $enabled
     */
    public static function setEnabled($enabled)
    {
        self::$enabled = $enabled;
    }
    
    /**
     * Lấy đường dẫn log file theo ngày
     * 
     * @param string $prefix
     * @return string
     */
    private static function getLogFile($prefix = 'api')
    {
        if (self::$logDir === null) {
            self::init();
        }
        
        return self::$logDir . '/' . $prefix . '_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Ghi log
     * 
     * @param string $level INFO, ERROR, DEBUG
     * @param string $message
     * @param array $context
     * @param string $prefix File prefix
     */
    public static function log($level, $message, array $context = [], $prefix = 'api')
    {
        if (!self::$enabled) {
            return;
        }
        
        $logFile = self::getLogFile($prefix);
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
        
        $logLine = "[{$timestamp}] [{$level}] {$message}";
        if ($contextStr) {
            $logLine .= "\n" . $contextStr;
        }
        $logLine .= "\n" . str_repeat('-', 80) . "\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log request
     * 
     * @param string $method HTTP method
     * @param string $url
     * @param array $headers
     * @param mixed $body
     */
    public static function logRequest($method, $url, array $headers = [], $body = null)
    {
        self::log('INFO', "REQUEST: {$method} {$url}", [
            'headers' => $headers,
            'body' => $body,
        ]);
    }
    
    /**
     * Log response
     * 
     * @param int $httpCode
     * @param mixed $body
     * @param float $duration Thời gian xử lý (giây)
     */
    public static function logResponse($httpCode, $body, $duration = null)
    {
        $message = "RESPONSE: HTTP {$httpCode}";
        if ($duration !== null) {
            $message .= " ({$duration}s)";
        }
        
        self::log('INFO', $message, [
            'body' => $body,
        ]);
    }
    
    /**
     * Log error
     * 
     * @param string $message
     * @param array $context
     */
    public static function error($message, array $context = [])
    {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log debug
     * 
     * @param string $message
     * @param array $context
     */
    public static function debug($message, array $context = [])
    {
        self::log('DEBUG', $message, $context);
    }
    
    /**
     * Log info
     * 
     * @param string $message
     * @param array $context
     */
    public static function info($message, array $context = [])
    {
        self::log('INFO', $message, $context);
    }
}
