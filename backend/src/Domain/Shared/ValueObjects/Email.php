<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object imutável representando um endereço de e-mail válido.
 */
final readonly class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $filtered = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        if ($filtered === false) {
            throw new InvalidArgumentException("O e-mail informado é inválido: {$email}");
        }
        $this->value = strtolower($filtered);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
