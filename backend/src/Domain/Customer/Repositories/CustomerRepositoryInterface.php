<?php

declare(strict_types=1);

namespace App\Domain\Customer\Repositories;

use App\Domain\Customer\Entities\Customer;
use App\Domain\Shared\ValueObjects\Email;

/**
 * Contrato de repositório para recuperação e persistência de clientes.
 */
interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;

    public function findByEmail(Email $email): ?Customer;

    public function save(Customer $customer): Customer;
}
