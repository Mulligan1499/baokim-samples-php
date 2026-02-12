<?php
/**
 * Class SignatureHelper
 * 
 * Xử lý ký số RSA SHA256 cho Baokim B2B API
 * 
 * Thuật toán: SHA256withRSA
 * - Data cần ký: json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
 * - Ký: base64_encode(rsa_sign(data, private_key))
 * - Verify: rsa_verify(data, base64_decode(signature), public_key)
 * 
 * @package Baokim\B2B
 */

namespace Baokim\B2B;

class SignatureHelper
{
    /**
     * Ký dữ liệu bằng Private Key
     * 
     * @param string $data Dữ liệu cần ký (JSON string của request body)
     * @param string $privateKeyPath Đường dẫn tới file private key PEM
     * @return string Chữ ký đã được base64 encode
     * @throws \Exception Nếu không thể đọc key hoặc ký thất bại
     */
    public static function sign($data, $privateKeyPath = null)
    {
        if ($privateKeyPath === null) {
            $privateKeyPath = Config::get('merchant_private_key_path');
        }
        
        // Đọc private key từ file
        if (!file_exists($privateKeyPath)) {
            throw new \Exception("Private key file not found: {$privateKeyPath}");
        }
        
        $privateKeyContent = file_get_contents($privateKeyPath);
        if ($privateKeyContent === false) {
            throw new \Exception("Cannot read private key file: {$privateKeyPath}");
        }
        
        $privateKey = openssl_pkey_get_private($privateKeyContent);
        if ($privateKey === false) {
            throw new \Exception("Invalid private key format. Error: " . openssl_error_string());
        }
        
        // Ký dữ liệu bằng SHA256withRSA
        $signature = '';
        $result = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        // Giải phóng key resource (PHP < 8.0)
        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($privateKey);
        }
        
        if ($result === false) {
            throw new \Exception("Failed to sign data. Error: " . openssl_error_string());
        }
        
        // Trả về signature đã base64 encode
        return base64_encode($signature);
    }
    
    /**
     * Xác thực chữ ký bằng Public Key
     * 
     * Dùng để verify webhook từ Baokim
     * 
     * @param string $data Dữ liệu gốc (JSON string từ webhook body)
     * @param string $signature Chữ ký đã base64 encode (từ header Signature)
     * @param string $publicKeyPath Đường dẫn tới file public key PEM của Baokim
     * @return bool True nếu chữ ký hợp lệ
     * @throws \Exception Nếu không thể đọc key
     */
    public static function verify($data, $signature, $publicKeyPath = null)
    {
        if ($publicKeyPath === null) {
            $publicKeyPath = Config::get('baokim_public_key_path');
        }
        
        // Đọc public key từ file
        if (!file_exists($publicKeyPath)) {
            throw new \Exception("Public key file not found: {$publicKeyPath}");
        }
        
        $publicKeyContent = file_get_contents($publicKeyPath);
        if ($publicKeyContent === false) {
            throw new \Exception("Cannot read public key file: {$publicKeyPath}");
        }
        
        $publicKey = openssl_pkey_get_public($publicKeyContent);
        if ($publicKey === false) {
            throw new \Exception("Invalid public key format. Error: " . openssl_error_string());
        }
        
        // Decode signature từ base64
        $signatureDecoded = base64_decode($signature);
        if ($signatureDecoded === false) {
            return false;
        }
        
        // Verify chữ ký
        $result = openssl_verify($data, $signatureDecoded, $publicKey, OPENSSL_ALGO_SHA256);
        
        // Giải phóng key resource (PHP < 8.0)
        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($publicKey);
        }
        
        // openssl_verify trả về: 1 = valid, 0 = invalid, -1 = error
        return $result === 1;
    }
    
    /**
     * Tạo cặp RSA key mới
     * 
     * Sử dụng khi Merchant cần tạo key pair mới
     * 
     * @param int $bits Độ dài key (mặc định 2048)
     * @return array ['private' => string, 'public' => string]
     * @throws \Exception Nếu không thể tạo key
     */
    public static function generateKeyPair($bits = 2048)
    {
        $config = [
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        
        $resource = openssl_pkey_new($config);
        if ($resource === false) {
            throw new \Exception("Failed to generate key pair. Error: " . openssl_error_string());
        }
        
        // Export private key
        $privateKey = '';
        openssl_pkey_export($resource, $privateKey);
        
        // Export public key
        $keyDetails = openssl_pkey_get_details($resource);
        $publicKey = $keyDetails['key'];
        
        return [
            'private' => $privateKey,
            'public' => $publicKey,
        ];
    }
    
    /**
     * Lưu key pair ra file
     * 
     * @param string $privateKeyPath Đường dẫn lưu private key
     * @param string $publicKeyPath Đường dẫn lưu public key
     * @param int $bits Độ dài key
     * @return bool
     * @throws \Exception
     */
    public static function generateAndSaveKeyPair($privateKeyPath, $publicKeyPath, $bits = 2048)
    {
        $keyPair = self::generateKeyPair($bits);
        
        // Tạo thư mục nếu chưa có
        $privateDir = dirname($privateKeyPath);
        if (!is_dir($privateDir)) {
            mkdir($privateDir, 0755, true);
        }
        
        $publicDir = dirname($publicKeyPath);
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        // Lưu files
        $result1 = file_put_contents($privateKeyPath, $keyPair['private']);
        $result2 = file_put_contents($publicKeyPath, $keyPair['public']);
        
        if ($result1 === false || $result2 === false) {
            throw new \Exception("Failed to save key files");
        }
        
        // Bảo mật private key
        chmod($privateKeyPath, 0600);
        
        return true;
    }
}
