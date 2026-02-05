<?php
declare(strict_types=1);

namespace Polyx\Engine;

use Polyx\Engine\Exception\ParserException;

/**
 * Parser - Token'ları Abstract Syntax Tree (AST) yapısına dönüştürür
 * 
 * Öncelik sırası (düşükten yükseğe):
 * 1. OR
 * 2. AND
 * 3. NOT
 * 4. Karşılaştırma operatörleri (==, !=, <, >, <=, >=)
 * 5. CONTAINS, IN
 * 6. Parantezler (en yüksek öncelik)
 * 
 * @package Polyx\Engine
 */
class Parser
{
    private array $tokens;
    private int $position;
    private int $length;

    // AST Node Tipleri
    public const NODE_BINARY     = 'BinaryExpression';
    public const NODE_UNARY      = 'UnaryExpression';
    public const NODE_LITERAL    = 'Literal';
    public const NODE_VARIABLE   = 'Variable';
    public const NODE_ARRAY      = 'ArrayLiteral';
    public const NODE_CONTAINS   = 'ContainsExpression';
    public const NODE_IN         = 'InExpression';

    /**
     * Token'ları parse et ve AST oluştur
     * 
     * @param array $tokens Token dizisi
     * @return array AST yapısı
     * @throws ParserException Parse hatası durumunda
     */
    public function parse(array $tokens): array
    {
        $this->tokens = $tokens;
        $this->position = 0;
        $this->length = count($tokens);

        // Boş input kontrolü
        if ($this->length === 0 || $this->currentToken()['type'] === Tokenizer::TOKEN_EOF) {
            throw new ParserException('Boş kural ifadesi');
        }

        $ast = $this->parseOrExpression();

        // Parantez dengesi kontrolü
        if ($this->currentToken()['type'] !== Tokenizer::TOKEN_EOF) {
            throw new ParserException(
                'Beklenmeyen token: ' . json_encode($this->currentToken()),
                $this->currentToken()
            );
        }

        return [
            'type' => 'Program',
            'body' => $ast,
            'meta' => [
                'tokenCount' => $this->length - 1,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Mevcut token'ı al
     */
    private function currentToken(): array
    {
        return $this->tokens[$this->position] ?? ['type' => Tokenizer::TOKEN_EOF, 'value' => ''];
    }

    /**
     * Sonraki token'a geç
     */
    private function advance(): array
    {
        $token = $this->currentToken();
        $this->position++;
        return $token;
    }

    /**
     * Token tipini kontrol et ve ilerle
     */
    private function expect(string $type): array
    {
        $token = $this->currentToken();
        if ($token['type'] !== $type) {
            throw new ParserException(
                "Beklenen token tipi: {$type}, bulunan: {$token['type']}",
                $token
            );
        }
        return $this->advance();
    }

    /**
     * OR ifadesi parse et (en düşük öncelik)
     */
    private function parseOrExpression(): array
    {
        $left = $this->parseAndExpression();

        while ($this->currentToken()['type'] === Tokenizer::TOKEN_LOGIC && 
               $this->currentToken()['value'] === 'OR') {
            $operator = $this->advance()['value'];
            $right = $this->parseAndExpression();
            
            $left = [
                'type' => self::NODE_BINARY,
                'operator' => $operator,
                'left' => $left,
                'right' => $right
            ];
        }

        return $left;
    }

    /**
     * AND ifadesi parse et
     */
    private function parseAndExpression(): array
    {
        $left = $this->parseNotExpression();

        while ($this->currentToken()['type'] === Tokenizer::TOKEN_LOGIC && 
               $this->currentToken()['value'] === 'AND') {
            $operator = $this->advance()['value'];
            $right = $this->parseNotExpression();
            
            $left = [
                'type' => self::NODE_BINARY,
                'operator' => $operator,
                'left' => $left,
                'right' => $right
            ];
        }

        return $left;
    }

    /**
     * NOT ifadesi parse et
     */
    private function parseNotExpression(): array
    {
        if ($this->currentToken()['type'] === Tokenizer::TOKEN_NOT) {
            $this->advance();
            $operand = $this->parseNotExpression();
            
            return [
                'type' => self::NODE_UNARY,
                'operator' => 'NOT',
                'operand' => $operand
            ];
        }

        return $this->parseComparison();
    }

    /**
     * Karşılaştırma ifadesi parse et
     */
    private function parseComparison(): array
    {
        $left = $this->parsePrimary();

        $token = $this->currentToken();

        // Karşılaştırma operatörleri
        if ($token['type'] === Tokenizer::TOKEN_OPERATOR) {
            $operator = $this->advance()['value'];
            $right = $this->parsePrimary();
            
            return [
                'type' => self::NODE_BINARY,
                'operator' => $operator,
                'left' => $left,
                'right' => $right
            ];
        }

        // CONTAINS operatörü
        if ($token['type'] === Tokenizer::TOKEN_CONTAINS) {
            $this->advance();
            $right = $this->parsePrimary();
            
            return [
                'type' => self::NODE_CONTAINS,
                'target' => $left,
                'search' => $right
            ];
        }

        // IN operatörü
        if ($token['type'] === Tokenizer::TOKEN_IN) {
            $this->advance();
            $right = $this->parseArray();
            
            return [
                'type' => self::NODE_IN,
                'value' => $left,
                'array' => $right
            ];
        }

        return $left;
    }

    /**
     * Array parse et
     */
    private function parseArray(): array
    {
        $this->expect(Tokenizer::TOKEN_LBRACKET);
        
        $elements = [];
        
        while ($this->currentToken()['type'] !== Tokenizer::TOKEN_RBRACKET) {
            $elements[] = $this->parsePrimary();
            
            if ($this->currentToken()['type'] === Tokenizer::TOKEN_COMMA) {
                $this->advance();
            }
        }
        
        $this->expect(Tokenizer::TOKEN_RBRACKET);
        
        return [
            'type' => self::NODE_ARRAY,
            'elements' => $elements
        ];
    }

    /**
     * Birincil ifade parse et (en yüksek öncelik)
     */
    private function parsePrimary(): array
    {
        $token = $this->currentToken();

        // Parantez
        if ($token['type'] === Tokenizer::TOKEN_LPAREN) {
            $this->advance();
            $expression = $this->parseOrExpression();
            $this->expect(Tokenizer::TOKEN_RPAREN);
            return $expression;
        }

        // Literal değerler
        if ($token['type'] === Tokenizer::TOKEN_NUMBER) {
            $this->advance();
            return [
                'type' => self::NODE_LITERAL,
                'valueType' => 'number',
                'value' => $token['value']
            ];
        }

        if ($token['type'] === Tokenizer::TOKEN_STRING) {
            $this->advance();
            return [
                'type' => self::NODE_LITERAL,
                'valueType' => 'string',
                'value' => $token['value']
            ];
        }

        if ($token['type'] === Tokenizer::TOKEN_BOOLEAN) {
            $this->advance();
            return [
                'type' => self::NODE_LITERAL,
                'valueType' => 'boolean',
                'value' => $token['value']
            ];
        }

        if ($token['type'] === Tokenizer::TOKEN_NULL) {
            $this->advance();
            return [
                'type' => self::NODE_LITERAL,
                'valueType' => 'null',
                'value' => null
            ];
        }

        // Değişken
        if ($token['type'] === Tokenizer::TOKEN_VARIABLE) {
            $this->advance();
            return [
                'type' => self::NODE_VARIABLE,
                'name' => $token['value'],
                'path' => explode('.', $token['value'])
            ];
        }

        // Array literal
        if ($token['type'] === Tokenizer::TOKEN_LBRACKET) {
            return $this->parseArray();
        }

        throw new ParserException(
            "Beklenmeyen token: {$token['type']} ({$token['value']})",
            $token
        );
    }

    /**
     * AST'yi görselleştirmek için format
     */
    public function formatAST(array $ast, int $indent = 0): string
    {
        $prefix = str_repeat('  ', $indent);
        $output = '';

        if (!isset($ast['type'])) {
            return $prefix . json_encode($ast);
        }

        switch ($ast['type']) {
            case 'Program':
                $output .= $prefix . "📋 Program\n";
                $output .= $this->formatAST($ast['body'], $indent + 1);
                break;

            case self::NODE_BINARY:
                $output .= $prefix . "🔗 {$ast['operator']}\n";
                $output .= $prefix . "├─ Sol:\n";
                $output .= $this->formatAST($ast['left'], $indent + 2);
                $output .= $prefix . "└─ Sağ:\n";
                $output .= $this->formatAST($ast['right'], $indent + 2);
                break;

            case self::NODE_UNARY:
                $output .= $prefix . "🚫 {$ast['operator']}\n";
                $output .= $this->formatAST($ast['operand'], $indent + 1);
                break;

            case self::NODE_LITERAL:
                $val = is_bool($ast['value']) ? ($ast['value'] ? 'true' : 'false') : $ast['value'];
                $output .= $prefix . "📌 {$ast['valueType']}: {$val}\n";
                break;

            case self::NODE_VARIABLE:
                $output .= $prefix . "📊 Değişken: {$ast['name']}\n";
                break;

            case self::NODE_CONTAINS:
                $output .= $prefix . "🔍 CONTAINS\n";
                $output .= $prefix . "├─ Hedef:\n";
                $output .= $this->formatAST($ast['target'], $indent + 2);
                $output .= $prefix . "└─ Arama:\n";
                $output .= $this->formatAST($ast['search'], $indent + 2);
                break;

            case self::NODE_IN:
                $output .= $prefix . "📋 IN\n";
                $output .= $prefix . "├─ Değer:\n";
                $output .= $this->formatAST($ast['value'], $indent + 2);
                $output .= $prefix . "└─ Dizi:\n";
                $output .= $this->formatAST($ast['array'], $indent + 2);
                break;

            case self::NODE_ARRAY:
                $output .= $prefix . "📦 Array [{$this->countElements($ast['elements'])} eleman]\n";
                foreach ($ast['elements'] as $i => $elem) {
                    $output .= $this->formatAST($elem, $indent + 1);
                }
                break;

            default:
                $output .= $prefix . json_encode($ast) . "\n";
        }

        return $output;
    }

    /**
     * Array element sayısı
     */
    private function countElements(array $elements): int
    {
        return count($elements);
    }
}
