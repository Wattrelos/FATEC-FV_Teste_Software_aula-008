<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object imutável encapsulando o hash seguro de senhas (BCrypt/Argon2id).
 * Garante que senhas em texto plano nunca vazem no domínio.
 */
final readonly class PasswordHash
{
    public const MIN_LENGTH = 6;
    public const MAX_LENGTH = 128;

    private string $hash;

    public function __construct(string $plainOrHash, bool $isAlreadyHashed = false)
    {
        if ($isAlreadyHashed) {
            $this->hash = $plainOrHash;

            return;
        }

        if (strlen($plainOrHash) < self::MIN_LENGTH) {
            throw new InvalidArgumentException("A senha deve ter no mínimo " . self::MIN_LENGTH . " caracteres.");
        }

        if (strlen($plainOrHash) > self::MAX_LENGTH) {
            throw new InvalidArgumentException("A senha excede o limite máximo permitido de " . self::MAX_LENGTH . " caracteres.");
        }

        $this->hash = password_hash($plainOrHash, PASSWORD_DEFAULT);
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash, true);
    }

    public function verify(string $plainPassword): bool
    {
        if (strlen($plainPassword) > self::MAX_LENGTH) {
            return false;
        }

        return password_verify($plainPassword, $this->hash);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
