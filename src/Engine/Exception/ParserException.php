<?php
declare(strict_types=1);

namespace Polyx\Engine\Exception;

/**
 * ParserException - Parser/AST hataları için özel istisna sınıfı
 * 
 * @package Polyx\Engine\Exception
 */
class ParserException extends \Exception
{
    private ?array $token;

    public function __construct(string $message, ?array $token = null)
    {
        $this->token = $token;
        parent::__construct($message);
    }

    public function getToken(): ?array
    {
        return $this->token;
    }
}
