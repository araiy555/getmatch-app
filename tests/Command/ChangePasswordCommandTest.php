<?php

namespace App\Tests\Command;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\ChangePasswordCommand
 */
class ChangePasswordCommandTest extends KernelTestCase {
    /**
     * @var Command
     */
    private $command;

    /**
     * @var UserRepository
     */
    private $users;

    protected function setUp(): void {
        $application = new Application(self::bootKernel());

        $this->command = $application->find('postmill:change-password');
        $this->users = self::$container->get(UserRepository::class);
    }

    public function testChangesPassword(): void {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['tortilla']);
        $tester->execute([
            'user' => 'emma',
        ]);

        $this->assertSame('tortilla', $this->users->loadUserByUsername('emma')->getPassword());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testChangesPasswordWhenLookingUpByUserId(): void {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['password']);
        $tester->execute([
            'user' => '1',
            '--find-by-id' => true,
        ]);

        $this->assertSame('password', $this->users->loadUserByUsername('emma')->getPassword());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testFailsOnNonExistentUser(): void {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'user' => 'george',
        ]);

        $this->assertStringContainsString('No such user "george"', $tester->getDisplay(true));
        $this->assertSame(1, $tester->getStatusCode());
    }
}
