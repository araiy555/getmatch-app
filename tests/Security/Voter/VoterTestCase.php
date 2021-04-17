<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

abstract class VoterTestCase extends TestCase {
    /**
     * @var VoterInterface
     */
    private $voter;

    /**
     * @var AccessDecisionManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $decisionManager;

    abstract protected function getVoter(): VoterInterface;

    protected function setUp(): void {
        $this->decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = $this->getVoter();
    }

    protected function assertDenied(string $attribute, $subject, TokenInterface $token): void {
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $subject, [$attribute])
        );
    }

    protected function assertGranted(string $attribute, $subject, TokenInterface $token): void {
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $subject, [$attribute])
        );
    }

    protected function expectRoleLookup(string $role, TokenInterface $token): void {
        $this->decisionManager
            ->expects($this->atLeastOnce())
            ->method('decide')
            ->with($token, [$role])
            ->willReturn(\in_array($role, $token->getRoleNames(), true));
    }

    protected function expectNoRoleLookup(): void {
        $this->decisionManager
            ->expects($this->never())
            ->method('decide');
    }

    /**
     * @param User|mixed|null $user
     */
    protected function createToken(array $roles, $user = null): TokenInterface {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn($roles);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
