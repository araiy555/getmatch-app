<?php

namespace App\Tests\Security;

use App\Security\Authentication;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @covers \App\Security\Authentication
 */
class AuthenticationTest extends TestCase {
    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenStorage;

    /**
     * @var Authentication
     */
    private $authentication;

    protected function setUp(): void {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authentication = new Authentication($this->tokenStorage);
    }

    public function testNoTokenInStorageReturnsNull(): void {
        $this->tokenStorage
            ->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn(null);

        $this->assertNull($this->authentication->getUser());

        $this->expectException(\RuntimeException::class);
        $this->authentication->getUserOrThrow();
    }

    public function testNoUserInTokenReturnsNull(): void {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn(null);

        $this->tokenStorage
            ->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->authentication->getUser());

        $this->expectException(\RuntimeException::class);
        $this->authentication->getUserOrThrow();
    }

    /**
     * @dataProvider provideMethods
     */
    public function testUserInTokenReturnsUser(string $method): void {
        $user = EntityFactory::makeUser();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($user, $this->authentication->{$method}());
    }

    /**
     * @dataProvider provideMethods
     */
    public function testWrongUserTypeInToken(string $method): void {
        $user = new \stdClass();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->expectException(\RuntimeException::class);
        $this->authentication->{$method}();
    }

    public function testScalarUserInToken(): void {
        $user = 'anon.';

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->authentication->getUser());

        $this->expectException(\RuntimeException::class);
        $this->authentication->getUserOrThrow();
    }

    public function provideMethods(): \Generator {
        yield ['getUser'];
        yield ['getUserOrThrow'];
    }
}
