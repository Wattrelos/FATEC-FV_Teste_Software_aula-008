<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Customer\DTOs\LoginInputDTO;
use App\Application\Customer\UseCases\AuthenticateCustomerUseCase;
use App\Infrastructure\Persistence\InMemoryCustomerRepository;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuthenticateCustomerUseCaseTest extends TestCase
{
    private InMemoryCustomerRepository $repository;
    private AuthenticateCustomerUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCustomerRepository();
        $this->useCase = new AuthenticateCustomerUseCase($this->repository);
    }

    public function testDeveAutenticarComSucessoQuandoCredenciaisForemValidas(): void
    {
        $input = new LoginInputDTO("usuario@teste.com", "Senha@123");
        $output = $this->useCase->execute($input);

        $this->assertEquals(1, $output->customerId);
        $this->assertEquals("Usuário Teste", $output->fullName);
        $this->assertEquals("usuario@teste.com", $output->email);
    }

    public function testDeveRejeitarQuandoUsuarioNaoExiste(): void
    {
        $input = new LoginInputDTO("inexistente@teste.com", "Senha@123");

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Credenciais inválidas.");
        $this->useCase->execute($input);
    }

    public function testDeveRejeitarQuandoSenhaEstiverIncorreta(): void
    {
        $input = new LoginInputDTO("usuario@teste.com", "SenhaErrada@999");

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Credenciais inválidas.");
        $this->useCase->execute($input);
    }

    public function testDeveGarantirPrevencaoDeEnumeracaoDeUsuarios(): void
    {
        // Regra OWASP: mensagens para usuário não cadastrado e senha incorreta DEVEM ser idênticas
        $msgUsuarioInexistente = null;

        try {
            $this->useCase->execute(new LoginInputDTO("desconhecido@teste.com", "QualquerSenha"));
        } catch (DomainException $e) {
            $msgUsuarioInexistente = $e->getMessage();
        }

        $msgSenhaIncorreta = null;

        try {
            $this->useCase->execute(new LoginInputDTO("usuario@teste.com", "SenhaIncorreta"));
        } catch (DomainException $e) {
            $msgSenhaIncorreta = $e->getMessage();
        }

        $this->assertNotNull($msgUsuarioInexistente);
        $this->assertNotNull($msgSenhaIncorreta);
        $this->assertEquals($msgUsuarioInexistente, $msgSenhaIncorreta);
        $this->assertEquals("Credenciais inválidas.", $msgUsuarioInexistente);
    }

    public function testDeveRejeitarQuandoContaEstiverInativa(): void
    {
        $input = new LoginInputDTO("inativo@teste.com", "Senha@123");

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Conta de cliente desativada.");
        $this->useCase->execute($input);
    }

    public function testDeveRejeitarQuandoCamposEstiveremVazios(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Preencha todos os campos obrigatórios.");
        $this->useCase->execute(new LoginInputDTO("", ""));
    }
}
