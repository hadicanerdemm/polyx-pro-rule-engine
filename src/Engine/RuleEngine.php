<?php
declare(strict_types=1);

namespace Polyx\Engine;

/**
 * RuleEngine - Ana kural motoru fasad sınıfı
 * 
 * Tokenizer, Parser ve Evaluator bileşenlerini bir araya getirerek
 * tek bir API üzerinden kural değerlendirmesi yapar.
 * 
 * @package Polyx\Engine
 */
class RuleEngine
{
    private Tokenizer $tokenizer;
    private Parser $parser;
    private Evaluator $evaluator;
    
    private float $startTime;
    private int $startMemory;
    private array $lastResult;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tokenizer = new Tokenizer();
        $this->parser = new Parser();
        $this->evaluator = new Evaluator();
        $this->lastResult = [];
    }

    /**
     * Kuralı değerlendir
     * 
     * @param string $rule Kural ifadesi
     * @param array $context Değişken konteksti
     * @return array Sonuç dizisi
     */
    public function execute(string $rule, array $context = []): array
    {
        $this->startProfiling();

        try {
            // Tokenize
            $tokens = $this->tokenizer->tokenize($rule);
            $tokenCount = $this->tokenizer->getTokenCount();

            // Parse
            $ast = $this->parser->parse($tokens);

            // Evaluate
            $evalResult = $this->evaluator->evaluate($ast, $context);

            $this->lastResult = [
                'success' => true,
                'decision' => (bool)$evalResult['result'],
                'message' => $evalResult['result'] ? 'ONAYLANDI' : 'REDDEDİLDİ',
                'meta' => $this->getMetrics($tokenCount, $evalResult['steps']),
                'debug_ast' => $ast,
                'evaluation_log' => $evalResult['log']
            ];

        } catch (\Polyx\Engine\Exception\TokenizerException $e) {
            $this->lastResult = $this->createError(
                'TOKENIZER_ERROR',
                $e->getMessage(),
                [
                    'position' => $e->getPosition(),
                    'invalid_char' => $e->getInvalidChar()
                ]
            );
        } catch (\Polyx\Engine\Exception\ParserException $e) {
            $this->lastResult = $this->createError(
                'PARSER_ERROR',
                $e->getMessage(),
                ['token' => $e->getToken()]
            );
        } catch (\Polyx\Engine\Exception\EvaluatorException $e) {
            $this->lastResult = $this->createError(
                'EVALUATOR_ERROR',
                $e->getMessage(),
                ['variable' => $e->getVariableName()]
            );
        } catch (\Exception $e) {
            $this->lastResult = $this->createError(
                'UNKNOWN_ERROR',
                $e->getMessage()
            );
        }

        return $this->lastResult;
    }

    /**
     * Sadece tokenize et
     */
    public function tokenize(string $rule): array
    {
        return $this->tokenizer->tokenize($rule);
    }

    /**
     * Sadece parse et
     */
    public function parse(string $rule): array
    {
        $tokens = $this->tokenizer->tokenize($rule);
        return $this->parser->parse($tokens);
    }

    /**
     * AST'yi formatlı string olarak al
     */
    public function formatAST(string $rule): string
    {
        $tokens = $this->tokenizer->tokenize($rule);
        $ast = $this->parser->parse($tokens);
        return $this->parser->formatAST($ast);
    }

    /**
     * Profiling başlat
     */
    private function startProfiling(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Metrikleri al
     */
    private function getMetrics(int $tokenCount, int $evalSteps): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = ($endTime - $this->startTime) * 1000; // ms
        $memoryUsed = $endMemory - $this->startMemory;

        return [
            'time' => $this->formatDuration($duration),
            'time_raw' => $duration,
            'memory' => $this->formatMemory($memoryUsed),
            'memory_raw' => $memoryUsed,
            'tokens' => $tokenCount,
            'evaluation_steps' => $evalSteps,
            'peak_memory' => $this->formatMemory(memory_get_peak_usage(true))
        ];
    }

    /**
     * Süreyi formatla
     */
    private function formatDuration(float $ms): string
    {
        if ($ms < 1) {
            return number_format($ms * 1000, 2) . ' µs';
        }
        if ($ms < 1000) {
            return number_format($ms, 2) . ' ms';
        }
        return number_format($ms / 1000, 2) . ' s';
    }

    /**
     * Belleği formatla
     */
    private function formatMemory(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return number_format($bytes / (1024 * 1024), 2) . ' MB';
    }

    /**
     * Hata yanıtı oluştur
     */
    private function createError(string $code, string $message, array $details = []): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ],
            'meta' => [
                'time' => $this->formatDuration((microtime(true) - $this->startTime) * 1000),
                'memory' => $this->formatMemory(memory_get_usage(true) - $this->startMemory)
            ]
        ];
    }

    /**
     * Son sonucu al
     */
    public function getLastResult(): array
    {
        return $this->lastResult;
    }
}
