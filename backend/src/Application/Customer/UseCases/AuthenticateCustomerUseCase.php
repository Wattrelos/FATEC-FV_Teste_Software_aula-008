<?php

declare(strict_types=1);

namespace App\Application\Customer\UseCases;

use App\Application\Customer\DTOs\LoginInputDTO;
use App\Application\Customer\DTOs\LoginOutputDTO;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Domain\Shared\ValueObjects\Email;
use DomainException;
use InvalidArgumentException;

/**
 * Caso de Uso: Autenticar Cliente.
 *
 * Regra de Segurança Crítica (Prevenção de Enumeração de Usuários):
 * Retorna mensagem idêntica ("Credenciais inválidas.") para usuário inexistente
 * e senha incorreta.
 */
final class AuthenticateCustomerUseCase
{
    /**
     * Hash BCrypt pré-computado para equalização de tempo (Anti-Timing Attack).
     * Garante que tentativas para usuários inexistentes consumam o mesmo tempo de CPU
     * que tentativas com senha incorreta para usuários reais.
     */
    private const DUMMY_HASH = '$2y$12$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW';

    public function __construct(
        private readonly CustomerRepositoryInterface $repository
    ) {
    }

    public function execute(LoginInputDTO $input): LoginOutputDTO
    {
        if (empty(trim($input->email)) || empty($input->plainPassword)) {
            throw new InvalidArgumentException("Preencha todos os campos obrigatórios.");
        }

        try {
            $email = new Email($input->email);
        } catch (InvalidArgumentException) {
            // Se o e-mail não tiver formato válido, executa dummy hash e lança erro genérico
            password_verify($input->plainPassword, self::DUMMY_HASH);

            throw new DomainException("Credenciais inválidas.");
        }

        $customer = $this->repository->findByEmail($email);

        if ($customer === null) {
            // Equalização de tempo: calcula hash BCrypt mesmo quando o usuário não existe
            password_verify($input->plainPassword, self::DUMMY_HASH);

            throw new DomainException("Credenciais inválidas.");
        }

        if (!$customer->isStatus()) {
            throw new DomainException("Conta de cliente desativada.");
        }

        if (!$customer->verifyPassword($input->plainPassword)) {
            throw new DomainException("Credenciais inválidas.");
        }

        return new LoginOutputDTO(
            customerId: (int) $customer->getId(),
            fullName: $customer->getFullName(),
            email: $customer->getEmail()->getValue()
        );
    }
}
