<?php
/**
 * Class WebhookHandler
 * 
 * Xử lý Webhook từ Baokim
 * 
 * Webhook types:
 * - Payment notification (thanh toán thành công)
 * - Refund notification (hoàn tiền)
 * - Cancel auto-debit notification (hủy thu hộ tự động)
 * 
 * @package Baokim\B2B
 */

namespace Baokim\B2B;

class WebhookHandler
{
    /**
     * Webhook operation types
     */
    const OPERATION_PAYMENT = 'PAYMENT_TRANS';
    const OPERATION_REFUND = 'REFUND_TRANS';
    const OPERATION_CANCEL_AUTO_DEBIT = 'CANCEL_AUTO_DEBIT';
    
    /**
     * Transaction status
     */
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 0;
    const STATUS_PENDING = 2;
    
    /**
     * @var string Raw request body
     */
    private $rawBody;
    
    /**
     * @var array Parsed payload
     */
    private $payload;
    
    /**
     * @var string Signature từ header
     */
    private $signature;
    
    /**
     * @var bool Signature đã được verify chưa
     */
    private $isVerified = false;
    
    /**
     * @var callable|null Callback handler cho payment
     */
    private $paymentHandler;
    
    /**
     * @var callable|null Callback handler cho refund
     */
    private $refundHandler;
    
    /**
     * Constructor
     * 
     * @param string|null $rawBody Raw request body (nếu null sẽ đọc từ php://input)
     * @param string|null $signature Signature từ header (nếu null sẽ đọc từ $_SERVER)
     */
    public function __construct($rawBody = null, $signature = null)
    {
        // Đọc raw body
        if ($rawBody === null) {
            $rawBody = file_get_contents('php://input');
        }
        $this->rawBody = $rawBody;
        
        // Parse JSON payload
        $this->payload = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->payload = null;
        }
        
        // Đọc signature từ header
        if ($signature === null) {
            $signature = $this->getSignatureFromHeader();
        }
        $this->signature = $signature;
    }
    
    /**
     * Lấy Signature từ HTTP header
     * 
     * @return string|null
     */
    private function getSignatureFromHeader()
    {
        // Thử các cách đọc header khác nhau
        if (isset($_SERVER['HTTP_SIGNATURE'])) {
            return $_SERVER['HTTP_SIGNATURE'];
        }
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Signature'])) {
                return $headers['Signature'];
            }
            // Case-insensitive
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'signature') {
                    return $value;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Xác thực chữ ký của webhook
     * 
     * @param string|null $publicKeyPath Đường dẫn public key của Baokim
     * @return bool
     * @throws \Exception
     */
    public function verifySignature($publicKeyPath = null)
    {
        if ($this->signature === null) {
            throw new \Exception('Signature header not found');
        }
        
        if (empty($this->rawBody)) {
            throw new \Exception('Empty request body');
        }
        
        $this->isVerified = SignatureHelper::verify($this->rawBody, $this->signature, $publicKeyPath);
        
        return $this->isVerified;
    }
    
    /**
     * Xử lý webhook
     * 
     * @param bool $verifySignature Có verify signature trước khi xử lý không
     * @return array Response để trả về Baokim
     * @throws \Exception
     */
    public function handle($verifySignature = true)
    {
        // Verify signature nếu cần
        if ($verifySignature) {
            if (!$this->verifySignature()) {
                return $this->errorResponse(104, 'Invalid signature');
            }
        }
        
        // Kiểm tra payload
        if ($this->payload === null) {
            return $this->errorResponse(422, 'Invalid JSON payload');
        }
        
        // Xác định loại webhook
        $operation = isset($this->payload['operation']) ? $this->payload['operation'] : null;
        
        try {
            switch ($operation) {
                case self::OPERATION_PAYMENT:
                    return $this->handlePayment();
                    
                case self::OPERATION_REFUND:
                    return $this->handleRefund();
                    
                case self::OPERATION_CANCEL_AUTO_DEBIT:
                    return $this->handleCancelAutoDebit();
                    
                default:
                    // VA webhook không có field operation
                    if (isset($this->payload['transaction']) || isset($this->payload['va_info'])) {
                        return $this->handleVAPayment();
                    }
                    return $this->errorResponse(422, 'Unknown operation type');
            }
        } catch (\Exception $e) {
            return $this->errorResponse(500, 'Handler error: ' . $e->getMessage());
        }
    }
    
    /**
     * Xử lý webhook thanh toán (Basic Pro)
     * 
     * @return array
     */
    protected function handlePayment()
    {
        $paymentResult = isset($this->payload['payment_result']) 
            ? $this->payload['payment_result'] 
            : null;
            
        if ($paymentResult === null) {
            return $this->errorResponse(422, 'payment_result not found');
        }
        
        // Call custom handler nếu có
        if ($this->paymentHandler !== null) {
            $result = call_user_func($this->paymentHandler, $paymentResult, $this->payload);
            if ($result !== null) {
                return $result;
            }
        }
        
        // Default: log và trả về success
        $this->logWebhook('payment', $paymentResult);
        
        return $this->successResponse();
    }
    
    /**
     * Xử lý webhook hoàn tiền (Basic Pro)
     * 
     * @return array
     */
    protected function handleRefund()
    {
        $paymentResult = isset($this->payload['payment_result']) 
            ? $this->payload['payment_result'] 
            : null;
            
        if ($paymentResult === null) {
            return $this->errorResponse(422, 'payment_result not found');
        }
        
        // Call custom handler nếu có
        if ($this->refundHandler !== null) {
            $result = call_user_func($this->refundHandler, $paymentResult, $this->payload);
            if ($result !== null) {
                return $result;
            }
        }
        
        $this->logWebhook('refund', $paymentResult);
        
        return $this->successResponse();
    }
    
    /**
     * Xử lý webhook hủy thu hộ tự động
     * 
     * @return array
     */
    protected function handleCancelAutoDebit()
    {
        $this->logWebhook('cancel_auto_debit', $this->payload);
        return $this->successResponse();
    }
    
    /**
     * Xử lý webhook thanh toán VA (Host to Host)
     * 
     * @return array
     */
    protected function handleVAPayment()
    {
        $transaction = isset($this->payload['transaction']) 
            ? $this->payload['transaction'] 
            : null;
        $vaInfo = isset($this->payload['va_info']) 
            ? $this->payload['va_info'] 
            : null;
        
        // Call custom handler nếu có
        if ($this->paymentHandler !== null) {
            $result = call_user_func($this->paymentHandler, [
                'transaction' => $transaction,
                'va_info' => $vaInfo,
            ], $this->payload);
            if ($result !== null) {
                return $result;
            }
        }
        
        $this->logWebhook('va_payment', [
            'transaction' => $transaction,
            'va_info' => $vaInfo,
        ]);
        
        return $this->successResponse();
    }
    
    /**
     * Set custom payment handler
     * 
     * Handler sẽ nhận: ($paymentData, $fullPayload)
     * Nếu trả về array sẽ dùng làm response, trả về null sẽ dùng default response
     * 
     * @param callable $handler
     * @return $this
     */
    public function onPayment(callable $handler)
    {
        $this->paymentHandler = $handler;
        return $this;
    }
    
    /**
     * Set custom refund handler
     * 
     * @param callable $handler
     * @return $this
     */
    public function onRefund(callable $handler)
    {
        $this->refundHandler = $handler;
        return $this;
    }
    
    /**
     * Lấy raw payload
     * 
     * @return array|null
     */
    public function getPayload()
    {
        return $this->payload;
    }
    
    /**
     * Lấy raw body
     * 
     * @return string
     */
    public function getRawBody()
    {
        return $this->rawBody;
    }
    
    /**
     * Kiểm tra đã verify signature chưa
     * 
     * @return bool
     */
    public function isVerified()
    {
        return $this->isVerified;
    }
    
    /**
     * Tạo success response
     * 
     * @param string $message
     * @return array
     */
    public function successResponse($message = 'Success')
    {
        return [
            'code' => 0,
            'message' => $message,
        ];
    }
    
    /**
     * Tạo error response
     * 
     * @param int $code
     * @param string $message
     * @return array
     */
    public function errorResponse($code, $message)
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }
    
    /**
     * Gửi JSON response
     * 
     * @param array $response
     * @return void
     */
    public function sendResponse(array $response)
    {
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Log webhook (override method này để custom logging)
     * 
     * @param string $type
     * @param array $data
     * @return void
     */
    protected function logWebhook($type, $data)
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/webhook_' . date('Y-m-d') . '.log';
        $logEntry = sprintf(
            "[%s] Type: %s\nData: %s\n---\n",
            date('Y-m-d H:i:s'),
            $type,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
