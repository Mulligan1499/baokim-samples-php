<?php
/**
 * Autoloader cho Baokim B2B Example
 * 
 * @package Baokim\B2B
 */

// Set timezone Việt Nam (Baokim API yêu cầu request_time đúng múi giờ)
date_default_timezone_set('Asia/Ho_Chi_Minh');

spl_autoload_register(function ($class) {
    // Chỉ xử lý namespace Baokim\B2B
    $prefix = 'Baokim\\B2B\\';
    $baseDir = __DIR__ . '/';
    
    // Kiểm tra class có thuộc namespace này không
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Lấy tên class (không có namespace prefix)
    $relativeClass = substr($class, $len);
    
    // Chuyển namespace thành đường dẫn file
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Nếu file tồn tại, require nó
    if (file_exists($file)) {
        require $file;
    }
});
