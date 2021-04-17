<?php

namespace App\Tests\DataTransfer;

use App\DataObject\SubmissionData;
use App\DataTransfer\SubmissionManager;
use App\Entity\ForumLogSubmissionLock;
use App\Entity\ForumLogSubmissionRestored;
use App\Entity\Image;
use App\Entity\Submission;
use App\Event\DeleteSubmission;
use App\Message\NewSubmission;
use App\Tests\Fixtures\Factory\EntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \App\DataTransfer\SubmissionManager
 * @group time-sensitive
 */
class SubmissionManagerTest extends TestCase {
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var MessageBusInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageBus;

    /**
     * @var SubmissionManager
     */
    private $manager;

    public static function setUpBeforeClass(): void {
        ClockMock::register(SubmissionManager::class);
    }

    protected function setUp(): void {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->manager = new SubmissionManager(
            $this->entityManager,
            $this->eventDispatcher,
            $this->messageBus,
        );
    }

    public function testSubmit(): void {
        $forum = EntityFactory::makeForum();
        $user = EntityFactory::makeUser();
        $data = new SubmissionData();
        $data->setTitle('submission title');
        $data->setBody('submission body');
        $data->setUrl('http://www.example.com/');
        $data->setForum($forum);
        $submission = new Submission(
            $data->getTitle(),
            $data->getUrl(),
            $data->getBody(),
            $forum,
            $user,
            '127.0.0.1',
        );
        $this->expectPersistAndDispatch($submission);

        $submitted = $this->manager->submit($data, $user, '127.0.0.1');

        $r = new \ReflectionProperty(Submission::class, 'id');
        $r->setAccessible(true);
        $r->setValue($submission, 123);
        $r->setAccessible(false);
        $this->assertEquals($submission, $submitted);
    }

    public function testSubmitImage(): void {
        $forum = EntityFactory::makeForum();
        $user = EntityFactory::makeUser();
        $image = new Image('a.png', random_bytes(32), 16, 16);
        $data = new SubmissionData();
        $data->setTitle('submission title');
        $data->setBody('submission body');
        $data->setForum($forum);
        $data->setMediaType(Submission::MEDIA_IMAGE);
        $data->setImage($image);
        $submission = new Submission(
            $data->getTitle(),
            null,
            $data->getBody(),
            $forum,
            $user,
            '127.0.0.1',
        );
        $submission->setMediaType(Submission::MEDIA_IMAGE);
        $submission->setImage($image);
        $this->expectPersistAndDispatch($submission);

        $submitted = $this->manager->submit($data, $user, '127.0.0.1');

        $this->assertSubmissionEquals($submission, $submitted);
    }

    /**
     * @dataProvider provideFieldsThatRecordTimeOfUpdate
     */
    public function testTimeOfUpdateIsRecorded(
        string $setter,
        string $value
    ): void {
        $submission = EntityFactory::makeSubmission();
        $data = SubmissionData::createFromSubmission($submission);
        $data->{$setter}($value);

        $this->manager->update($submission, $data, $submission->getUser());

        $this->assertNotNull($submission->getEditedAt());
        $this->assertSame(time(), $submission->getEditedAt()->getTimestamp());
    }

    /**
     * @dataProvider provideFieldsThatRecordTimeOfUpdate
     */
    public function testTimeOfUpdateIsNotRecordedIfFieldIsSame(
        string $setter,
        string $value
    ): void {
        $submission = EntityFactory::makeSubmission();
        $submission->{$setter}($value);
        $data = SubmissionData::createFromSubmission($submission);
        $data->{$setter}($value);

        $this->manager->update($submission, $data, $submission->getUser());

        $this->assertNull($submission->getEditedAt());
    }

    public function provideFieldsThatRecordTimeOfUpdate(): \Generator {
        yield ['setTitle', 'some title'];
        yield ['setBody', 'some body'];
        yield ['setUrl', 'some url'];
    }

    /**
     * @dataProvider provideLocked
     */
    public function testLockingIsLogged(bool $locked): void {
        $submission = EntityFactory::makeSubmission();
        $submission->setLocked(!$locked);
        $data = SubmissionData::createFromSubmission($submission);
        $data->setLocked($locked);

        $this->manager->update($submission, $data, $submission->getUser());

        $logEntries = $submission->getForum()->getPaginatedLogEntries(1);
        $this->assertCount(1, $logEntries);
        /** @var ForumLogSubmissionLock $logEntry */
        $logEntry = iterator_to_array($logEntries)[0];
        $this->assertSame($locked, $logEntry->getLocked());
    }

    public function provideLocked(): \Generator {
        yield [true];
        yield [false];
    }

    public function testDelete(): void {
        $submission = EntityFactory::makeSubmission();
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(new DeleteSubmission($submission));

        $this->manager->delete($submission);
    }

    public function testRemove(): void {
        $submission = EntityFactory::makeSubmission();
        $user = EntityFactory::makeUser();
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                (new DeleteSubmission($submission))
                    ->asModerator($user, 'wah'),
            );

        $this->manager->remove($submission, $user, 'wah');
    }

    public function testPurge(): void {
        $submission = EntityFactory::makeSubmission();
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with((new DeleteSubmission($submission))->withPermanence());

        $this->manager->purge($submission);
    }

    public function testRestore(): void {
        $forum = EntityFactory::makeForum();
        $user = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission($forum);
        $submission->trash();

        $this->manager->restore($submission, $user);

        $this->assertTrue($submission->isVisible());
        $logEntries = iterator_to_array($forum->getPaginatedLogEntries(1));
        $this->assertCount(1, $logEntries);
        $this->assertInstanceOf(ForumLogSubmissionRestored::class, $logEntries[0]);
    }

    private function expectPersistAndDispatch(Submission $submission): void {
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($submission))
            ->willReturnCallback(function (Submission $submission) {
                // hack to give submission an ID
                $r = new \ReflectionProperty(Submission::class, 'id');
                $r->setAccessible(true);
                $r->setValue($submission, 123);
                $r->setAccessible(false);
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (NewSubmission $message) {
                return $message->getSubmissionId() === 123;
            }))
            ->willReturnCallback(function ($message) {
                return new Envelope($message, []);
            });
    }

    private function assertSubmissionEquals(
        Submission $expected,
        Submission $actual
    ): void {
        $r = new \ReflectionProperty(Submission::class, 'id');
        $r->setAccessible(true);
        $r->setValue($actual, $expected->getId());
        $r->setAccessible(false);

        $this->assertEquals($expected, $actual);
    }
}
