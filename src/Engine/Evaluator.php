<?php
declare(strict_types=1);

namespace Polyx\Engine;

use Polyx\Engine\Exception\EvaluatorException;

/**
 * Evaluator - AST'yi değerlendirir ve sonuç üretir
 * 
 * GÜVENLİK: eval() KULLANILMAZ!
 * Tüm karşılaştırmalar manuel ve tip-güvenli yapılır.
 * 
 * Özellikler:
 * - Short-circuit evaluation (kısa devre değerlendirmesi)
 * - Tip güvenli karşılaştırmalar
 * - Dot-notation değişken çözümleme
 * - Detaylı hata raporlama
 * 
 * @package Polyx\Engine
 */
class Evaluator
{
    private array $context;
    private array $evaluationLog;
    private int $stepCount;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->context = [];
        $this->evaluationLog = [];
        $this->stepCount = 0;
    }

    /**
     * AST'yi değerlendir
     * 
     * @param array $ast Abstract Syntax Tree
     * @param array $context Değişken konteksti
     * @return array Değerlendirme sonucu
     * @throws EvaluatorException Değerlendirme hatası durumunda
     */
    public function evaluate(array $ast, array $context = []): array
    {
        $this->context = $context;
        $this->evaluationLog = [];
        $this->stepCount = 0;

        // Program node'unu handle et
        $nodeToEvaluate = isset($ast['type']) && $ast['type'] === 'Program' 
            ? $ast['body'] 
            : $ast;

        $result = $this->evaluateNode($nodeToEvaluate);

        return [
            'result' => $result,
            'steps' => $this->stepCount,
            'log' => $this->evaluationLog
        ];
    }

    /**
     * AST node'unu değerlendir
     * 
     * @param array $node AST node
     * @return mixed Değerlendirme sonucu
     */
    private function evaluateNode(array $node): mixed
    {
        $this->stepCount++;

        return match ($node['type']) {
            Parser::NODE_LITERAL => $this->evaluateLiteral($node),
            Parser::NODE_VARIABLE => $this->evaluateVariable($node),
            Parser::NODE_BINARY => $this->evaluateBinary($node),
            Parser::NODE_UNARY => $this->evaluateUnary($node),
            Parser::NODE_CONTAINS => $this->evaluateContains($node),
            Parser::NODE_IN => $this->evaluateIn($node),
            Parser::NODE_ARRAY => $this->evaluateArray($node),
            default => throw new EvaluatorException("Bilinmeyen node tipi: {$node['type']}")
        };
    }

    /**
     * Literal değeri değerlendir
     */
    private function evaluateLiteral(array $node): mixed
    {
        $this->log("Literal değer: {$node['valueType']} = " . json_encode($node['value']));
        return $node['value'];
    }

    /**
     * Değişkeni değerlendir (dot-notation destekli)
     */
    private function evaluateVariable(array $node): mixed
    {
        $path = $node['path'];
        $value = $this->context;

        foreach ($path as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } elseif (is_object($value) && property_exists($value, $key)) {
                $value = $value->$key;
            } else {
                $this->log("Değişken bulunamadı: {$node['name']}");
                throw new EvaluatorException(
                    "Değişken bulunamadı: {$node['name']}",
                    $node['name']
                );
            }
        }

        $this->log("Değişken çözümlendi: {$node['name']} = " . json_encode($value));
        return $value;
    }

    /**
     * Binary ifadeyi değerlendir (kısa devre optimizasyonu ile)
     */
    private function evaluateBinary(array $node): mixed
    {
        $operator = $node['operator'];

        // Short-circuit evaluation için AND ve OR özel işleme
        if ($operator === 'AND') {
            $left = $this->evaluateNode($node['left']);
            $leftBool = $this->toBoolean($left);
            
            $this->log("AND Sol taraf: " . ($leftBool ? 'true' : 'false'));
            
            // Kısa devre: Sol false ise sağı değerlendirme
            if (!$leftBool) {
                $this->log("AND Kısa devre: Sol false, değerlendirme durduruluyor");
                return false;
            }
            
            $right = $this->evaluateNode($node['right']);
            $rightBool = $this->toBoolean($right);
            
            $this->log("AND Sağ taraf: " . ($rightBool ? 'true' : 'false'));
            
            return $leftBool && $rightBool;
        }

        if ($operator === 'OR') {
            $left = $this->evaluateNode($node['left']);
            $leftBool = $this->toBoolean($left);
            
            $this->log("OR Sol taraf: " . ($leftBool ? 'true' : 'false'));
            
            // Kısa devre: Sol true ise sağı değerlendirme
            if ($leftBool) {
                $this->log("OR Kısa devre: Sol true, değerlendirme durduruluyor");
                return true;
            }
            
            $right = $this->evaluateNode($node['right']);
            $rightBool = $this->toBoolean($right);
            
            $this->log("OR Sağ taraf: " . ($rightBool ? 'true' : 'false'));
            
            return $leftBool || $rightBool;
        }

        // Karşılaştırma operatörleri
        $left = $this->evaluateNode($node['left']);
        $right = $this->evaluateNode($node['right']);

        $result = $this->compare($left, $right, $operator);
        
        $this->log("Karşılaştırma: " . json_encode($left) . " {$operator} " . json_encode($right) . " = " . ($result ? 'true' : 'false'));

        return $result;
    }

    /**
     * Unary ifadeyi değerlendir
     */
    private function evaluateUnary(array $node): bool
    {
        $operand = $this->evaluateNode($node['operand']);
        
        if ($node['operator'] === 'NOT') {
            $result = !$this->toBoolean($operand);
            $this->log("NOT operatörü: " . ($result ? 'true' : 'false'));
            return $result;
        }

        throw new EvaluatorException("Bilinmeyen unary operatör: {$node['operator']}");
    }

    /**
     * CONTAINS ifadesini değerlendir
     */
    private function evaluateContains(array $node): bool
    {
        $target = $this->evaluateNode($node['target']);
        $search = $this->evaluateNode($node['search']);

        // String içinde arama
        if (is_string($target) && is_string($search)) {
            $result = str_contains($target, $search);
            $this->log("CONTAINS (string): '{$target}' içinde '{$search}' = " . ($result ? 'bulundu' : 'bulunamadı'));
            return $result;
        }

        // Array içinde arama
        if (is_array($target)) {
            $result = in_array($search, $target, true);
            $this->log("CONTAINS (array): dizi içinde " . json_encode($search) . " = " . ($result ? 'bulundu' : 'bulunamadı'));
            return $result;
        }

        throw new EvaluatorException("CONTAINS operatörü yalnızca string veya array üzerinde kullanılabilir");
    }

    /**
     * IN ifadesini değerlendir
     */
    private function evaluateIn(array $node): bool
    {
        $value = $this->evaluateNode($node['value']);
        $array = $this->evaluateNode($node['array']);

        if (!is_array($array)) {
            throw new EvaluatorException("IN operatörü sağ tarafta array bekler");
        }

        $result = in_array($value, $array, false); // Loose comparison for mixed types
        $this->log("IN: " . json_encode($value) . " dizide " . ($result ? 'bulundu' : 'bulunamadı'));
        
        return $result;
    }

    /**
     * Array'i değerlendir
     */
    private function evaluateArray(array $node): array
    {
        $result = [];
        foreach ($node['elements'] as $element) {
            $result[] = $this->evaluateNode($element);
        }
        return $result;
    }

    /**
     * Karşılaştırma yap (tip-güvenli)
     * 
     * GÜVENLİK: eval() YOK!
     */
    private function compare(mixed $left, mixed $right, string $operator): bool
    {
        // Null kontrolü
        if ($left === null || $right === null) {
            return match ($operator) {
                '==' => $left === $right,
                '!=' => $left !== $right,
                default => false
            };
        }

        // String karşılaştırma
        if (is_string($left) && is_string($right)) {
            return match ($operator) {
                '==' => $left === $right,
                '!=' => $left !== $right,
                '>' => strcmp($left, $right) > 0,
                '<' => strcmp($left, $right) < 0,
                '>=' => strcmp($left, $right) >= 0,
                '<=' => strcmp($left, $right) <= 0,
                default => throw new EvaluatorException("Bilinmeyen operatör: {$operator}")
            };
        }

        // Sayı karşılaştırma (mixed types)
        if (is_numeric($left) && is_numeric($right)) {
            $left = (float)$left;
            $right = (float)$right;
            
            return match ($operator) {
                '==' => abs($left - $right) < PHP_FLOAT_EPSILON,
                '!=' => abs($left - $right) >= PHP_FLOAT_EPSILON,
                '>' => $left > $right,
                '<' => $left < $right,
                '>=' => $left >= $right,
                '<=' => $left <= $right,
                default => throw new EvaluatorException("Bilinmeyen operatör: {$operator}")
            };
        }

        // Boolean karşılaştırma
        if (is_bool($left) && is_bool($right)) {
            return match ($operator) {
                '==' => $left === $right,
                '!=' => $left !== $right,
                default => throw new EvaluatorException("Boolean değerler yalnızca == ve != ile karşılaştırılabilir")
            };
        }

        // Mixed type karşılaştırma (loose)
        return match ($operator) {
            '==' => $left == $right,
            '!=' => $left != $right,
            default => throw new EvaluatorException("Farklı tipler yalnızca == ve != ile karşılaştırılabilir")
        };
    }

    /**
     * Değeri boolean'a çevir
     */
    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return $value != 0;
        }
        if (is_string($value)) {
            return $value !== '';
        }
        if (is_array($value)) {
            return count($value) > 0;
        }
        if (is_null($value)) {
            return false;
        }
        return (bool)$value;
    }

    /**
     * Değerlendirme loguna ekle
     */
    private function log(string $message): void
    {
        $this->evaluationLog[] = [
            'step' => $this->stepCount,
            'message' => $message,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Değerlendirme logunu al
     */
    public function getLog(): array
    {
        return $this->evaluationLog;
    }

    /**
     * Adım sayısını al
     */
    public function getStepCount(): int
    {
        return $this->stepCount;
    }
}
