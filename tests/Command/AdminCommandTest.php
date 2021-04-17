<?php

namespace App\Tests\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\AdminCommand
 */
class AdminCommandTest extends KernelTestCase {
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

        $this->command = $application->find('postmill:admin');
        $this->users = self::$kernel->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);
    }

    public function testCanGiveAdmin(): void {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'command' => $this->command->getName(),
            'user' => ['zach', 'third'],
        ]);

        $this->assertStringContainsString('2 user(s) were admined', $tester->getDisplay());

        $this->assertTrue($this->users->findOneByUsername('zach')->isAdmin());
        $this->assertTrue($this->users->findOneByUsername('third')->isAdmin());
    }

    public function testCanRemoveAdmin(): void {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'command' => $this->command->getName(),
            'user' => ['emma'],
            '--remove' => true,
        ]);

        $this->assertStringContainsString('1 user(s) were de-admined', $tester->getDisplay());

        $this->assertFalse($this->users->findOneByUsername('emma')->isAdmin());
    }
}
