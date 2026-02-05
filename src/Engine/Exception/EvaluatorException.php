<?php
declare(strict_types=1);

namespace Polyx\Engine\Exception;

/**
 * EvaluatorException - Değerlendirici hataları için özel istisna sınıfı
 * 
 * @package Polyx\Engine\Exception
 */
class EvaluatorException extends \Exception
{
    private string $variableName;

    public function __construct(string $message, string $variableName = '')
    {
        $this->variableName = $variableName;
        parent::__construct($message);
    }

    public function getVariableName(): string
    {
        return $this->variableName;
    }
}
