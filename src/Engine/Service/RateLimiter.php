<?php
declare(strict_types=1);

namespace Polyx\Engine\Service;

/**
 * RateLimiter - API hız sınırlama servisi
 * 
 * IP bazlı rate limiting ile API'yi aşırı kullanıma karşı korur.
 * Token bucket algoritması kullanır.
 * 
 * @package Polyx\Engine\Service
 */
class RateLimiter
{
    private string $storageDir;
    private int $maxRequests;
    private int $windowSeconds;
    private array $rules;

    /**
     * Constructor
     * 
     * @param int $maxRequests Pencere başına maks istek
     * @param int $windowSeconds Zaman penceresi (saniye)
     * @param string|null $storageDir Veri depolama dizini
     */
    public function __construct(
        int $maxRequests = 60,
        int $windowSeconds = 60,
        ?string $storageDir = null
    ) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/polyx_ratelimit';
        $this->rules = [];
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Özel kural ekle
     * 
     * @param string $identifier IP veya API key
     * @param int $maxRequests Maks istek
     * @param int $windowSeconds Zaman penceresi
     * @return self
     */
    public function addRule(string $identifier, int $maxRequests, int $windowSeconds): self
    {
        $this->rules[$identifier] = [
            'max' => $maxRequests,
            'window' => $windowSeconds
        ];
        return $this;
    }

    /**
     * İstek kontrolü yap
     * 
     * @param string $identifier IP adresi veya API key
     * @return array Kontrol sonucu
     */
    public function check(string $identifier): array
    {
        $rule = $this->rules[$identifier] ?? [
            'max' => $this->maxRequests,
            'window' => $this->windowSeconds
        ];

        $data = $this->getData($identifier);
        $now = time();

        // Eski kayıtları temizle
        $data['requests'] = array_filter(
            $data['requests'] ?? [],
            fn($timestamp) => $timestamp > ($now - $rule['window'])
        );

        $requestCount = count($data['requests']);
        $remaining = max(0, $rule['max'] - $requestCount);
        $allowed = $requestCount < $rule['max'];

        // Reset zamanını hesapla
        $resetTime = $now + $rule['window'];
        if (!empty($data['requests'])) {
            $oldestRequest = min($data['requests']);
            $resetTime = $oldestRequest + $rule['window'];
        }

        return [
            'allowed' => $allowed,
            'limit' => $rule['max'],
            'remaining' => $remaining,
            'reset' => $resetTime,
            'reset_in' => $resetTime - $now,
            'retry_after' => $allowed ? 0 : ($resetTime - $now),
            'identifier' => $this->hashIdentifier($identifier)
        ];
    }

    /**
     * İstek kaydet
     * 
     * @param string $identifier IP adresi veya API key
     * @return bool Kayıt başarılı mı
     */
    public function hit(string $identifier): bool
    {
        $check = $this->check($identifier);
        
        if (!$check['allowed']) {
            return false;
        }

        $data = $this->getData($identifier);
        $data['requests'][] = time();
        $this->setData($identifier, $data);

        return true;
    }

    /**
     * Rate limit header'larını al
     * 
     * @param string $identifier
     * @return array HTTP header'ları
     */
    public function getHeaders(string $identifier): array
    {
        $check = $this->check($identifier);
        
        $headers = [
            'X-RateLimit-Limit' => (string)$check['limit'],
            'X-RateLimit-Remaining' => (string)$check['remaining'],
            'X-RateLimit-Reset' => (string)$check['reset']
        ];

        if (!$check['allowed']) {
            $headers['Retry-After'] = (string)$check['retry_after'];
        }

        return $headers;
    }

    /**
     * Header'ları uygula
     * 
     * @param string $identifier
     */
    public function applyHeaders(string $identifier): void
    {
        foreach ($this->getHeaders($identifier) as $name => $value) {
            header("{$name}: {$value}");
        }
    }

    /**
     * İstemci IP adresini al
     * 
     * @return string
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_CLIENT_IP',            // Bazı proxy'ler
            'REMOTE_ADDR'                // Direkt bağlantı
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For birden fazla IP içerebilir
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * Identifier hash'le (privacy için)
     * 
     * @param string $identifier
     * @return string
     */
    private function hashIdentifier(string $identifier): string
    {
        return substr(hash('sha256', $identifier), 0, 12);
    }

    /**
     * Depolama dosya yolu
     * 
     * @param string $identifier
     * @return string
     */
    private function getFilePath(string $identifier): string
    {
        return $this->storageDir . '/' . $this->hashIdentifier($identifier) . '.json';
    }

    /**
     * Veri oku
     * 
     * @param string $identifier
     * @return array
     */
    private function getData(string $identifier): array
    {
        $file = $this->getFilePath($identifier);
        
        if (!file_exists($file)) {
            return ['requests' => []];
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? ['requests' => []];
    }

    /**
     * Veri yaz
     * 
     * @param string $identifier
     * @param array $data
     */
    private function setData(string $identifier, array $data): void
    {
        $file = $this->getFilePath($identifier);
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Eski kayıtları temizle (garbage collection)
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $now = time();
        $maxAge = $this->windowSeconds * 2;

        foreach (glob($this->storageDir . '/*.json') as $file) {
            if (filemtime($file) < ($now - $maxAge)) {
                unlink($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * İstatistikleri al
     * 
     * @return array
     */
    public function getStats(): array
    {
        $files = glob($this->storageDir . '/*.json');
        $totalRequests = 0;
        $activeClients = count($files);

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            $totalRequests += count($data['requests'] ?? []);
        }

        return [
            'active_clients' => $activeClients,
            'total_requests' => $totalRequests,
            'storage_dir' => $this->storageDir,
            'default_limit' => $this->maxRequests,
            'default_window' => $this->windowSeconds
        ];
    }
}
