<?php

declare(strict_types=1);

namespace App\Application\Customer\DTOs;

/**
 * DTO de entrada para autenticação com higienização defensiva contra XSS e Injeções.
 */
final readonly class LoginInputDTO
{
    public string $email;
    public string $plainPassword;

    public function __construct(
        string $email,
        string $plainPassword
    ) {
        // 1. Remove tags HTML/scripts maliciosos e espaços desnecessários
        $sanitizedEmail = trim(strip_tags($email));

        // 2. Remove null bytes e caracteres perigosos de injeção
        $this->email = str_replace(["\0", "\x00"], '', $sanitizedEmail);
        $this->plainPassword = str_replace(["\0", "\x00"], '', $plainPassword);
    }
}
