<?php
declare(strict_types=1);

namespace Polyx\Engine;

use Polyx\Engine\Exception\TokenizerException;

/**
 * Tokenizer (Lexer) - Kural ifadelerini token'lara ayırır
 * 
 * Desteklenen özellikler:
 * - Dot-notation değişkenler (örn: user.finance.salary)
 * - Stringler (tek/çift tırnak)
 * - Sayılar (integer ve float)
 * - Boolean değerler (true, false)
 * - Karşılaştırma operatörleri (>=, <=, ==, !=, >, <)
 * - Mantıksal operatörler (AND, OR, NOT)
 * - Parantezler
 * 
 * @package Polyx\Engine
 */
class Tokenizer
{
    // Token tipleri
    public const TOKEN_VARIABLE     = 'VARIABLE';
    public const TOKEN_STRING       = 'STRING';
    public const TOKEN_NUMBER       = 'NUMBER';
    public const TOKEN_BOOLEAN      = 'BOOLEAN';
    public const TOKEN_OPERATOR     = 'OPERATOR';
    public const TOKEN_LOGIC        = 'LOGIC';
    public const TOKEN_LPAREN       = 'LPAREN';
    public const TOKEN_RPAREN       = 'RPAREN';
    public const TOKEN_EOF          = 'EOF';
    public const TOKEN_CONTAINS     = 'CONTAINS';
    public const TOKEN_IN           = 'IN';
    public const TOKEN_NOT          = 'NOT';
    public const TOKEN_LBRACKET     = 'LBRACKET';
    public const TOKEN_RBRACKET     = 'RBRACKET';
    public const TOKEN_COMMA        = 'COMMA';
    public const TOKEN_NULL         = 'NULL';

    private string $input;
    private int $position;
    private int $length;
    private array $tokens;

    /**
     * Tokenizer constructor
     */
    public function __construct()
    {
        $this->tokens = [];
        $this->position = 0;
        $this->length = 0;
        $this->input = '';
    }

    /**
     * Kural ifadesini tokenize et
     * 
     * @param string $rule Kural ifadesi
     * @return array Token dizisi
     * @throws TokenizerException Geçersiz karakter durumunda
     */
    public function tokenize(string $rule): array
    {
        $this->input = trim($rule);
        $this->position = 0;
        $this->length = strlen($this->input);
        $this->tokens = [];

        while ($this->position < $this->length) {
            $this->skipWhitespace();
            
            if ($this->position >= $this->length) {
                break;
            }

            $char = $this->currentChar();

            // Parantezler
            if ($char === '(') {
                $this->tokens[] = $this->createToken(self::TOKEN_LPAREN, '(');
                $this->advance();
                continue;
            }

            if ($char === ')') {
                $this->tokens[] = $this->createToken(self::TOKEN_RPAREN, ')');
                $this->advance();
                continue;
            }

            // Köşeli parantezler (array için)
            if ($char === '[') {
                $this->tokens[] = $this->createToken(self::TOKEN_LBRACKET, '[');
                $this->advance();
                continue;
            }

            if ($char === ']') {
                $this->tokens[] = $this->createToken(self::TOKEN_RBRACKET, ']');
                $this->advance();
                continue;
            }

            // Virgül
            if ($char === ',') {
                $this->tokens[] = $this->createToken(self::TOKEN_COMMA, ',');
                $this->advance();
                continue;
            }

            // Stringler
            if ($char === '"' || $char === "'") {
                $this->tokens[] = $this->readString($char);
                continue;
            }

            // Sayılar
            if (is_numeric($char) || ($char === '-' && $this->isNextNumeric())) {
                $this->tokens[] = $this->readNumber();
                continue;
            }

            // Operatörler
            if ($this->isOperatorStart($char)) {
                $this->tokens[] = $this->readOperator();
                continue;
            }

            // Kelimeler (değişkenler, boolean, mantıksal operatörler)
            if ($this->isWordStart($char)) {
                $this->tokens[] = $this->readWord();
                continue;
            }

            // Geçersiz karakter
            throw new TokenizerException(
                "Geçersiz karakter tespit edildi: '{$char}' (pozisyon: {$this->position})",
                $this->position,
                $char
            );
        }

        $this->tokens[] = $this->createToken(self::TOKEN_EOF, '');
        
        return $this->tokens;
    }

    /**
     * Token sayısını döndür
     */
    public function getTokenCount(): int
    {
        return count($this->tokens) - 1; // EOF hariç
    }

    /**
     * Boşlukları atla
     */
    private function skipWhitespace(): void
    {
        while ($this->position < $this->length && ctype_space($this->input[$this->position])) {
            $this->advance();
        }
    }

    /**
     * Mevcut karakteri al
     */
    private function currentChar(): string
    {
        return $this->input[$this->position] ?? '';
    }

    /**
     * Sonraki karaktere geç
     */
    private function advance(): void
    {
        $this->position++;
    }

    /**
     * İleriye bak
     */
    private function peek(int $offset = 1): string
    {
        $pos = $this->position + $offset;
        return $pos < $this->length ? $this->input[$pos] : '';
    }

    /**
     * Sonraki karakter sayı mı?
     */
    private function isNextNumeric(): bool
    {
        $next = $this->peek();
        return $next !== '' && (is_numeric($next) || $next === '.');
    }

    /**
     * Operatör başlangıcı mı?
     */
    private function isOperatorStart(string $char): bool
    {
        return in_array($char, ['>', '<', '=', '!'], true);
    }

    /**
     * Kelime başlangıcı mı?
     */
    private function isWordStart(string $char): bool
    {
        return ctype_alpha($char) || $char === '_';
    }

    /**
     * Kelime karakteri mi?
     */
    private function isWordChar(string $char): bool
    {
        return ctype_alnum($char) || $char === '_' || $char === '.';
    }

    /**
     * String oku
     */
    private function readString(string $quote): array
    {
        $startPos = $this->position;
        $this->advance(); // Açılış tırnağını atla
        
        $value = '';
        $escaped = false;

        while ($this->position < $this->length) {
            $char = $this->currentChar();

            if ($escaped) {
                $value .= $char;
                $escaped = false;
            } elseif ($char === '\\') {
                $escaped = true;
            } elseif ($char === $quote) {
                $this->advance(); // Kapanış tırnağını atla
                return $this->createToken(self::TOKEN_STRING, $value);
            } else {
                $value .= $char;
            }

            $this->advance();
        }

        throw new TokenizerException(
            "Kapatılmamış string ifadesi (pozisyon: {$startPos})",
            $startPos,
            $quote
        );
    }

    /**
     * Sayı oku
     */
    private function readNumber(): array
    {
        $value = '';
        $hasDecimal = false;

        // Negatif işareti
        if ($this->currentChar() === '-') {
            $value .= '-';
            $this->advance();
        }

        while ($this->position < $this->length) {
            $char = $this->currentChar();

            if (is_numeric($char)) {
                $value .= $char;
            } elseif ($char === '.' && !$hasDecimal) {
                $hasDecimal = true;
                $value .= $char;
            } else {
                break;
            }

            $this->advance();
        }

        $numValue = $hasDecimal ? (float)$value : (int)$value;
        return $this->createToken(self::TOKEN_NUMBER, $numValue);
    }

    /**
     * Operatör oku
     */
    private function readOperator(): array
    {
        $char = $this->currentChar();
        $next = $this->peek();

        // İki karakterli operatörler
        $twoChar = $char . $next;
        if (in_array($twoChar, ['>=', '<=', '==', '!='], true)) {
            $this->advance();
            $this->advance();
            return $this->createToken(self::TOKEN_OPERATOR, $twoChar);
        }

        // Tek karakterli operatörler
        if (in_array($char, ['>', '<'], true)) {
            $this->advance();
            return $this->createToken(self::TOKEN_OPERATOR, $char);
        }

        throw new TokenizerException(
            "Beklenmeyen operatör karakteri: '{$char}' (pozisyon: {$this->position})",
            $this->position,
            $char
        );
    }

    /**
     * Kelime oku (değişken, boolean, mantıksal operatör)
     */
    private function readWord(): array
    {
        $value = '';

        while ($this->position < $this->length && $this->isWordChar($this->currentChar())) {
            $value .= $this->currentChar();
            $this->advance();
        }

        $upper = strtoupper($value);

        // Boolean
        if ($upper === 'TRUE') {
            return $this->createToken(self::TOKEN_BOOLEAN, true);
        }
        if ($upper === 'FALSE') {
            return $this->createToken(self::TOKEN_BOOLEAN, false);
        }

        // NULL
        if ($upper === 'NULL') {
            return $this->createToken(self::TOKEN_NULL, null);
        }

        // Mantıksal operatörler
        if (in_array($upper, ['AND', 'OR', 'VE', 'VEYA'], true)) {
            $normalized = match($upper) {
                'VE' => 'AND',
                'VEYA' => 'OR',
                default => $upper
            };
            return $this->createToken(self::TOKEN_LOGIC, $normalized);
        }

        // NOT operatörü
        if (in_array($upper, ['NOT', 'DEĞİL'], true)) {
            return $this->createToken(self::TOKEN_NOT, 'NOT');
        }

        // CONTAINS operatörü
        if (in_array($upper, ['CONTAINS', 'İÇERİR', 'ICERIR'], true)) {
            return $this->createToken(self::TOKEN_CONTAINS, 'CONTAINS');
        }

        // IN operatörü
        if (in_array($upper, ['IN', 'İÇİNDE', 'ICINDE'], true)) {
            return $this->createToken(self::TOKEN_IN, 'IN');
        }

        // Değişken (dot-notation destekli)
        return $this->createToken(self::TOKEN_VARIABLE, $value);
    }

    /**
     * Token oluştur
     */
    private function createToken(string $type, mixed $value): array
    {
        return [
            'type' => $type,
            'value' => $value,
            'position' => $this->position
        ];
    }
}
