<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Shared\ValueObjects\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testDeveCriarEmailValidoComSucesso(): void
    {
        $email = new Email("Usuario.Teste@Dominio.COM");
        $this->assertEquals("usuario.teste@dominio.com", $email->getValue());
        $this->assertEquals("usuario.teste@dominio.com", (string) $email);
    }

    public function testDeveLancarExcecaoParaEmailInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("O e-mail informado é inválido");
        new Email("email_sem_arroba.com");
    }

    public function testDeveCompararEmailsIguais(): void
    {
        $email1 = new Email("teste@exemplo.com");
        $email2 = new Email("TESTE@EXEMPLO.COM");
        $email3 = new Email("outro@exemplo.com");

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }
}
