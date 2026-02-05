<?php
declare(strict_types=1);

namespace Polyx\Engine\Service;

/**
 * ErrorHandler - Merkezi hata yönetimi servisi
 * 
 * Tüm hataları yakalar, loglar ve JSON formatında döndürür.
 * Production ve development modları destekler.
 * 
 * @package Polyx\Engine\Service
 */
class ErrorHandler
{
    private static ?self $instance = null;
    private bool $debug;
    private string $logFile;
    private array $errorLog;
    private bool $jsonOutput;

    /**
     * Singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->debug = false;
        $this->logFile = sys_get_temp_dir() . '/polyx_errors.log';
        $this->errorLog = [];
        $this->jsonOutput = true;
    }

    /**
     * Hata yakalayıcıları kaydet
     */
    public function register(): self
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
        
        return $this;
    }

    /**
     * Debug modunu ayarla
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Log dosyasını ayarla
     */
    public function setLogFile(string $path): self
    {
        $this->logFile = $path;
        return $this;
    }

    /**
     * JSON çıktısını ayarla
     */
    public function setJsonOutput(bool $json): self
    {
        $this->jsonOutput = $json;
        return $this;
    }

    /**
     * PHP error handler
     */
    public function handleError(
        int $errno,
        string $errstr,
        string $errfile = '',
        int $errline = 0
    ): bool {
        // Suppressed errors için
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = $this->getErrorType($errno);
        
        $error = [
            'type' => $errorType,
            'code' => $errno,
            'message' => $errstr,
            'file' => $this->sanitizePath($errfile),
            'line' => $errline,
            'timestamp' => date('c')
        ];

        $this->log($error);

        // Fatal error'lar için exception fırlat
        if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        }

        return true;
    }

    /**
     * Exception handler
     */
    public function handleException(\Throwable $exception): void
    {
        $error = [
            'type' => 'EXCEPTION',
            'class' => get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $this->sanitizePath($exception->getFile()),
            'line' => $exception->getLine(),
            'timestamp' => date('c')
        ];

        if ($this->debug) {
            $error['trace'] = $this->sanitizeTrace($exception->getTrace());
        }

        $this->log($error);
        $this->output($error, 500);
    }

    /**
     * Shutdown handler (fatal errors)
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'FATAL_ERROR',
                'code' => $error['type'],
                'message' => $error['message'],
                'file' => $this->sanitizePath($error['file']),
                'line' => $error['line'],
                'timestamp' => date('c')
            ];

            $this->log($errorData);
            
            // Output buffer'ı temizle
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $this->output($errorData, 500);
        }
    }

    /**
     * Hatayı logla
     */
    private function log(array $error): void
    {
        $this->errorLog[] = $error;

        $logLine = sprintf(
            "[%s] %s: %s in %s:%d\n",
            $error['timestamp'],
            $error['type'],
            $error['message'],
            $error['file'] ?? 'unknown',
            $error['line'] ?? 0
        );

        error_log($logLine, 3, $this->logFile);
    }

    /**
     * Hata çıktısı
     */
    private function output(array $error, int $statusCode): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($statusCode);

        if ($this->jsonOutput) {
            header('Content-Type: application/json; charset=utf-8');
            
            $response = [
                'success' => false,
                'error' => [
                    'code' => $error['type'],
                    'message' => $this->debug ? $error['message'] : 'Bir hata oluştu',
                ]
            ];

            if ($this->debug) {
                $response['error']['details'] = $error;
            }

            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            echo "Hata: " . ($this->debug ? $error['message'] : 'Bir hata oluştu');
        }

        exit(1);
    }

    /**
     * Error type string'e çevir
     */
    private function getErrorType(int $errno): string
    {
        return match ($errno) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => 'UNKNOWN_ERROR'
        };
    }

    /**
     * Dosya yolunu sanitize et (güvenlik için)
     */
    private function sanitizePath(string $path): string
    {
        if (!$this->debug) {
            return basename($path);
        }
        return str_replace('\\', '/', $path);
    }

    /**
     * Stack trace'i sanitize et
     */
    private function sanitizeTrace(array $trace): array
    {
        return array_map(function ($item) {
            return [
                'file' => isset($item['file']) ? $this->sanitizePath($item['file']) : null,
                'line' => $item['line'] ?? null,
                'function' => $item['function'] ?? null,
                'class' => $item['class'] ?? null,
                'type' => $item['type'] ?? null
            ];
        }, array_slice($trace, 0, 10)); // Max 10 frame
    }

    /**
     * Error log'u al
     */
    public function getErrorLog(): array
    {
        return $this->errorLog;
    }

    /**
     * Manuel hata raporla
     */
    public function report(string $message, string $type = 'CUSTOM_ERROR', array $context = []): void
    {
        $error = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('c')
        ];

        $this->log($error);
    }

    /**
     * JSON hata yanıtı oluştur
     */
    public static function jsonError(
        string $message,
        string $code = 'ERROR',
        int $statusCode = 400,
        array $details = []
    ): never {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ],
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        exit(1);
    }
}
