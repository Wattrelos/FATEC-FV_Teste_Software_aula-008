<?php

declare(strict_types=1);

namespace App\Domain\Customer\Entities;

use App\Domain\Shared\ValueObjects\Email;
use App\Domain\Shared\ValueObjects\PasswordHash;

/**
 * Entidade de Domínio representando um Usuário/Cliente autenticável.
 */
final class Customer
{
    public function __construct(
        private ?int $id,
        private string $fullName,
        private Email $email,
        private PasswordHash $passwordHash,
        private bool $status = true
    ) {}

    public static function create(
        string $fullName,
        Email $email,
        PasswordHash $passwordHash,
        bool $status = true,
        ?int $id = null
    ): self {
        return new self($id, $fullName, $email, $passwordHash, $status);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function isStatus(): bool
    {
        return $this->status;
    }

    public function activate(): void
    {
        $this->status = true;
    }

    public function deactivate(): void
    {
        $this->status = false;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return $this->passwordHash->verify($plainPassword);
    }
}
