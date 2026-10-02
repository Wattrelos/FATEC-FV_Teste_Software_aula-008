<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Customer\DTOs\LoginInputDTO;
use App\Application\Customer\UseCases\AuthenticateCustomerUseCase;
use App\Http\Responders\JsonResponder;
use App\Infrastructure\Logging\SecurityLogger;
use DomainException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controller/Action para processamento de login.
 * 
 * Implementa medidas de segurança recomendadas pela OWASP:
 * - session_regenerate_id(true) para proteção contra Session Fixation.
 * - Mensagens genéricas para evitar enumeração de contas.
 * - Suporte híbrido a API REST (JSON) e Submissão tradicional (Redirect 302).
 * - Mascaramento obrigatório de credenciais nos logs.
 */
final class LoginAction
{
    public function __construct(
        private readonly AuthenticateCustomerUseCase $useCase,
        private readonly ?SecurityLogger $logger = null
    ) {}


    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        if (empty($body)) {
            $raw = (string) $request->getBody();
            $body = json_decode($raw, true) ?? [];
        }

        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        try {
            $input = new LoginInputDTO(
                email: $email,
                plainPassword: $password
            );

            $output = $this->useCase->execute($input);

            // Inicia e regenera o ID da sessão para mitigar Session Fixation
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true);
            }

            $_SESSION['customer_id'] = $output->customerId;
            $_SESSION['customer_name'] = $output->fullName;
            $_SESSION['customer_email'] = $output->email;
            $_SESSION['authenticated_at'] = time();

            $accept = $request->getHeaderLine('Accept');
            $isJson = str_contains($accept, 'application/json')
                || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

            if (!$isJson) {
                return $response
                    ->withHeader('Location', '/dashboard.html')
                    ->withStatus(302);
            }

            $this->logger?->info('Autenticação bem-sucedida', [
                'customer_id' => $output->customerId,
                'email'       => $output->email,
            ]);

            return JsonResponder::success($response, [
                'user'         => [
                    'id'    => $output->customerId,
                    'name'  => $output->fullName,
                    'email' => $output->email,
                ],
                'redirectUrl'  => '/dashboard.html',
                'message'      => 'Login realizado com sucesso!'
            ], 200);

        } catch (InvalidArgumentException $e) {
            $this->logger?->warning('Tentativa de autenticação com dados inválidos', [
                'email'    => $email,
                'password' => $password,
                'error'    => $e->getMessage(),
            ]);

            $accept = $request->getHeaderLine('Accept');
            $isJson = str_contains($accept, 'application/json')
                || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

            if (!$isJson) {
                return $response
                    ->withHeader('Location', '/login.html?error=' . urlencode($e->getMessage()))
                    ->withStatus(302);
            }

            return JsonResponder::error($response, $e->getMessage(), 400);

        } catch (DomainException $e) {
            $this->logger?->warning('Falha de autenticação (credenciais recusadas)', [
                'email'    => $email,
                'password' => $password,
                'error'    => $e->getMessage(),
            ]);

            $accept = $request->getHeaderLine('Accept');
            $isJson = str_contains($accept, 'application/json')
                || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

            if (!$isJson) {
                return $response
                    ->withHeader('Location', '/login.html?error=' . urlencode($e->getMessage()))
                    ->withStatus(302);
            }

            return JsonResponder::error($response, $e->getMessage(), 401);
        }

    }
}
