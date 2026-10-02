<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Shared\ValueObjects\PasswordHash;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PasswordHashTest extends TestCase
{
    public function testDeveGerarHashParaSenhaValida(): void
    {
        $plain = "Senha@Segura123";
        $passwordHash = new PasswordHash($plain);

        $this->assertNotEmpty($passwordHash->getHash());
        $this->assertNotEquals($plain, $passwordHash->getHash());
        $this->assertTrue($passwordHash->verify($plain));
        $this->assertFalse($passwordHash->verify("SenhaErrada"));
    }

    public function testDeveRejeitarSenhaComMenosDeSeisCaracteres(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A senha deve ter no mínimo 6 caracteres.");
        new PasswordHash("12345");
    }

    public function testDeveInstanciarDeHashPreExistente(): void
    {
        $hashOriginal = password_hash("SenhaExistente", PASSWORD_DEFAULT);
        $passwordHash = PasswordHash::fromHash($hashOriginal);

        $this->assertEquals($hashOriginal, $passwordHash->getHash());
        $this->assertTrue($passwordHash->verify("SenhaExistente"));
    }
}
