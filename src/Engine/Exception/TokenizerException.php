<?php
declare(strict_types=1);

namespace Polyx\Engine\Exception;

/**
 * TokenizerException - Lexer/Tokenizer hataları için özel istisna sınıfı
 * 
 * @package Polyx\Engine\Exception
 */
class TokenizerException extends \Exception
{
    private int $position;
    private string $invalidChar;

    public function __construct(string $message, int $position = 0, string $invalidChar = '')
    {
        $this->position = $position;
        $this->invalidChar = $invalidChar;
        parent::__construct($message);
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getInvalidChar(): string
    {
        return $this->invalidChar;
    }
}
