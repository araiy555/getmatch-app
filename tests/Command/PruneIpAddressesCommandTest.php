<?php

namespace App\Tests\Command;

use App\Entity\Comment;
use App\Entity\CommentVote;
use App\Entity\Message;
use App\Entity\Submission;
use App\Entity\SubmissionVote;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\PruneIpAddressesCommand
 */
class PruneIpAddressesCommandTest extends KernelTestCase {
    /**
     * @var Command
     */
    private $command;

    protected function setUp(): void {
        $application = new Application(self::bootKernel());

        $this->command = $application->find('postmill:prune-ips');
    }

    public function testPruning(): void {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['yes']);
        $tester->execute([]);

        $this->assertIpCount(0, Comment::class);
        $this->assertIpCount(0, CommentVote::class);
        $this->assertIpCount(0, Message::class);
        $this->assertIpCount(0, Submission::class);
        $this->assertIpCount(0, SubmissionVote::class);

        $this->assertStringContainsString('Pruned IPs for 8 entities.', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testDryRun(): void {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['yes']);
        $tester->execute([
            '--dry-run' => true,
        ]);

        $this->assertIpCount(2, Comment::class);
        $this->assertIpCount(2, CommentVote::class);
        $this->assertIpCount(1, Message::class);
        $this->assertIpCount(1, Submission::class);
        $this->assertIpCount(1, SubmissionVote::class);

        $this->assertStringContainsString('Pruned IPs for 8 entities', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testPruneOlderThanGivenTime(): void {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['yes']);
        $tester->execute([
            '--max-age' => '2017-06-01 00:00',
        ]);

        $this->assertIpCount(0, Comment::class);
        $this->assertIpCount(2, CommentVote::class);
        $this->assertIpCount(1, Message::class);
        $this->assertIpCount(0, Submission::class);
        $this->assertIpCount(1, SubmissionVote::class);

        $this->assertStringContainsString('Pruned IPs for 4 entities', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testCannotProvideFutureTimeAsMaxAgeOption(): void {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['yes']);
        $tester->execute([
            '--max-age' => '+69 hours',
        ]);

        $this->assertStringContainsString('max-age option cannot be a future time', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }

    private function assertIpCount(int $count, string $entityClass): void {
        $criteria = (new Criteria())->where(Criteria::expr()->neq('ip', null));

        $repository = self::$container
            ->get(EntityManagerInterface::class)
            ->getRepository($entityClass);

        $this->assertCount($count, $repository->matching($criteria));
    }
}
