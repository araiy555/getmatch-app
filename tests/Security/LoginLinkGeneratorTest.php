<?php

namespace App\Tests\Security;

use App\Security\LoginLinkGenerator;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\LoginLink\LoginLinkDetails;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * @covers \App\Security\LoginLinkGenerator
 */
class LoginLinkGeneratorTest extends TestCase {
    public function testCanGenerateLoginUrl(): void {
        $user = EntityFactory::makeUser();

        $handler = $this->createMock(LoginLinkHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('createLoginLink')
            ->with($user)
            ->willReturn(new LoginLinkDetails(
                'http://login.example.com/?secret=stuff',
                new \DateTimeImmutable(),
            ));

        $generator = new LoginLinkGenerator($handler);

        $this->assertEquals(
            'http://login.example.com/?secret=stuff&_remember_me=1',
            $generator->generate($user),
        );
    }
}
