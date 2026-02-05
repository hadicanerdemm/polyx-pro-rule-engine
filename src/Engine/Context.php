<?php
declare(strict_types=1);

namespace Polyx\Engine;

/**
 * Context - Güvenli veri erişim katmanı
 * 
 * Dot-notation ile nested array/object verilerine güvenli erişim sağlar.
 * Veri kapsülleme (encapsulation) ve null-safe erişim.
 * 
 * @package Polyx\Engine
 */
class Context
{
    private array $data;
    private array $accessLog;
    private bool $strictMode;

    /**
     * Constructor
     * 
     * @param array $data Kontekst verisi
     * @param bool $strictMode True ise eksik değişkenler exception fırlatır
     */
    public function __construct(array $data = [], bool $strictMode = true)
    {
        $this->data = $data;
        $this->accessLog = [];
        $this->strictMode = $strictMode;
    }

    /**
     * Dot-notation ile değer al
     * 
     * @param string $path Dot-notation path (örn: user.finance.salary)
     * @param mixed $default Varsayılan değer
     * @return mixed
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $this->logAccess($path);
        
        $keys = explode('.', $path);
        $value = $this->data;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } elseif (is_object($value) && property_exists($value, $key)) {
                $value = $value->$key;
            } else {
                if ($this->strictMode && $default === null) {
                    throw new Exception\EvaluatorException(
                        "Değişken bulunamadı: {$path}",
                        $path
                    );
                }
                return $default;
            }
        }

        return $value;
    }

    /**
     * Değer mevcut mu kontrol et
     * 
     * @param string $path Dot-notation path
     * @return bool
     */
    public function has(string $path): bool
    {
        try {
            $originalStrict = $this->strictMode;
            $this->strictMode = false;
            $value = $this->get($path, $this);
            $this->strictMode = $originalStrict;
            return $value !== $this;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Değer ayarla
     * 
     * @param string $path Dot-notation path
     * @param mixed $value Değer
     * @return self
     */
    public function set(string $path, mixed $value): self
    {
        $keys = explode('.', $path);
        $current = &$this->data;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }

        return $this;
    }

    /**
     * Birden fazla değeri merge et
     * 
     * @param array $data Merge edilecek veri
     * @return self
     */
    public function merge(array $data): self
    {
        $this->data = array_merge_recursive($this->data, $data);
        return $this;
    }

    /**
     * Ham veriyi al
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * JSON olarak dönüştür
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Erişim logunu al
     * 
     * @return array
     */
    public function getAccessLog(): array
    {
        return $this->accessLog;
    }

    /**
     * Tüm kullanılan path'leri al
     * 
     * @return array
     */
    public function getUsedPaths(): array
    {
        return array_unique(array_column($this->accessLog, 'path'));
    }

    /**
     * Erişimi logla
     * 
     * @param string $path Erişilen path
     */
    private function logAccess(string $path): void
    {
        $this->accessLog[] = [
            'path' => $path,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Strict mode'u ayarla
     * 
     * @param bool $strict
     * @return self
     */
    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;
        return $this;
    }

    /**
     * Veri yapısını doğrula
     * 
     * @param array $schema Beklenen yapı şeması
     * @return array Doğrulama sonucu
     */
    public function validate(array $schema): array
    {
        $errors = [];
        $validated = [];

        foreach ($schema as $path => $rules) {
            $exists = $this->has($path);
            $value = $exists ? $this->get($path) : null;

            // Required kontrolü
            if (($rules['required'] ?? false) && !$exists) {
                $errors[] = "Zorunlu alan eksik: {$path}";
                continue;
            }

            // Tip kontrolü
            if ($exists && isset($rules['type'])) {
                $actualType = gettype($value);
                $expectedType = $rules['type'];
                
                if ($actualType !== $expectedType) {
                    $errors[] = "Tip uyuşmazlığı: {$path} ({$expectedType} bekleniyor, {$actualType} bulundu)";
                }
            }

            // Min/Max kontrolü (sayılar için)
            if ($exists && is_numeric($value)) {
                if (isset($rules['min']) && $value < $rules['min']) {
                    $errors[] = "{$path} değeri minimum {$rules['min']} olmalı";
                }
                if (isset($rules['max']) && $value > $rules['max']) {
                    $errors[] = "{$path} değeri maksimum {$rules['max']} olmalı";
                }
            }

            if ($exists) {
                $validated[$path] = $value;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'validated' => $validated
        ];
    }

    /**
     * Debug için string temsili
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
