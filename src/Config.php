<?php
/**
 * Class Config
 * 
 * Quản lý cấu hình ứng dụng
 * 
 * @package Baokim\B2B
 */

namespace Baokim\B2B;

class Config
{
    /**
     * @var array Mảng chứa cấu hình
     */
    private static $config = null;
    
    /**
     * @var string Đường dẫn tới file config
     */
    private static $configPath = null;
    
    /**
     * Load cấu hình từ file
     * 
     * @param string|null $configPath Đường dẫn tới file config (optional)
     * @return void
     * @throws \Exception Nếu file config không tồn tại
     */
    public static function load($configPath = null)
    {
        if ($configPath === null) {
            // Ưu tiên file config.local.php nếu có
            $localConfigPath = __DIR__ . '/../config/config.local.php';
            $defaultConfigPath = __DIR__ . '/../config/config.php';
            
            if (file_exists($localConfigPath)) {
                $configPath = $localConfigPath;
            } elseif (file_exists($defaultConfigPath)) {
                $configPath = $defaultConfigPath;
            } else {
                throw new \Exception('Config file not found. Please create config/config.php');
            }
        }
        
        if (!file_exists($configPath)) {
            throw new \Exception("Config file not found: {$configPath}");
        }
        
        self::$configPath = $configPath;
        self::$config = require $configPath;
    }
    
    /**
     * Lấy giá trị config theo key
     * 
     * @param string $key Tên key cần lấy
     * @param mixed $default Giá trị mặc định nếu key không tồn tại
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (self::$config === null) {
            self::load();
        }
        
        return isset(self::$config[$key]) ? self::$config[$key] : $default;
    }
    
    /**
     * Lấy toàn bộ config
     * 
     * @return array
     */
    public static function all()
    {
        if (self::$config === null) {
            self::load();
        }
        
        return self::$config;
    }
    
    /**
     * Set giá trị config (runtime only)
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value)
    {
        if (self::$config === null) {
            self::load();
        }
        
        self::$config[$key] = $value;
    }
    
    /**
     * Kiểm tra key có tồn tại không
     * 
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        if (self::$config === null) {
            self::load();
        }
        
        return isset(self::$config[$key]);
    }
    
    /**
     * Reset config (dùng cho testing)
     * 
     * @return void
     */
    public static function reset()
    {
        self::$config = null;
        self::$configPath = null;
    }
}
