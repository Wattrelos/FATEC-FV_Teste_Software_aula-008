<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Customer\Entities\Customer;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Domain\Shared\ValueObjects\Email;
use App\Domain\Shared\ValueObjects\PasswordHash;

/**
 * Repositório em memória autocontido para execução de testes rápidos e isolados.
 * Elimina a dependência de bancos relacionais como MySQL/PostgreSQL.
 */
final class InMemoryCustomerRepository implements CustomerRepositoryInterface
{
    /** @var array<int, Customer> */
    private array $customers = [];

    private int $nextId = 1;

    public function __construct()
    {
        $this->seedDefaults();
    }

    public function seedDefaults(): void
    {
        $this->customers = [];
        $this->nextId = 1;

        // 1. Usuário padrão de testes (válido e ativo)
        $this->save(new Customer(
            id: null,
            fullName: "Usuário Teste",
            email: new Email("usuario@teste.com"),
            passwordHash: new PasswordHash("Senha@123"),
            status: true
        ));

        // 2. Usuário cadastrado porém inativo/bloqueado
        $this->save(new Customer(
            id: null,
            fullName: "Usuário Inativo",
            email: new Email("inativo@teste.com"),
            passwordHash: new PasswordHash("Senha@123"),
            status: false
        ));

        // 3. Usuário administrador
        $this->save(new Customer(
            id: null,
            fullName: "Administrador FATEC",
            email: new Email("admin@teste.com"),
            passwordHash: new PasswordHash("Admin@123"),
            status: true
        ));
    }

    public function findById(int $id): ?Customer
    {
        return $this->customers[$id] ?? null;
    }

    public function findByEmail(Email $email): ?Customer
    {
        foreach ($this->customers as $customer) {
            if ($customer->getEmail()->equals($email)) {
                return $customer;
            }
        }

        return null;
    }

    public function save(Customer $customer): Customer
    {
        if ($customer->getId() === null) {
            $customer->setId($this->nextId++);
        }
        $this->customers[$customer->getId()] = $customer;

        return $customer;
    }

    public function clear(): void
    {
        $this->customers = [];
        $this->nextId = 1;
    }
}
