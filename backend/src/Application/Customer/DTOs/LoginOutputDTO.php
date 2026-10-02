<?php

declare(strict_types=1);

namespace App\Application\Customer\DTOs;

final readonly class LoginOutputDTO
{
    public function __construct(
        public int $customerId,
        public string $fullName,
        public string $email
    ) {
    }
}
