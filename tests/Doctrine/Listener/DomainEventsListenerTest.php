<?php

namespace App\Tests\Doctrine\Listener;

use App\Doctrine\Listener\DomainEventsListener;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Event\ForumDeleted;
use App\Event\ForumUpdated;
use App\Event\SubmissionCreated;
use App\Tests\Fixtures\Factory\EntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Doctrine\Listener\DomainEventsListener
 */
class DomainEventsListenerTest extends TestCase {
    public function testDispatchesCreateEvent(): void {
        $entity = new Submission('a', null, null, EntityFactory::makeForum(), EntityFactory::makeUser(), null);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $args = new LifecycleEventArgs($entity, $entityManager);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($event) {
                return $event instanceof SubmissionCreated;
            }));

        $listener = new DomainEventsListener($dispatcher);
        $listener->postPersist($args);
    }

    public function testDispatchesDeleteEvent(): void {
        $entity = EntityFactory::makeForum();

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $args = new LifecycleEventArgs($entity, $entityManager);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($event) {
                return $event instanceof ForumDeleted;
            }));

        $listener = new DomainEventsListener($dispatcher);
        $listener->postRemove($args);
    }

    public function testDispatchesUpdateEvent(): void {
        $entity = EntityFactory::makeForum();
        $entity->setName('Paul');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata
            ->method('getName')
            ->willReturn(Forum::class);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $changeSet = ['name' => ['John', 'Paul']];
        $preArgs = new PreUpdateEventArgs($entity, $entityManager, $changeSet);
        $postArgs = new LifecycleEventArgs($entity, $entityManager);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($event) {
                return $event instanceof ForumUpdated &&
                    $event->getBefore()->getName() === 'John' &&
                    $event->getAfter()->getName() === 'Paul';
            }));

        $listener = new DomainEventsListener($dispatcher);
        $listener->preUpdate($preArgs);
        $listener->postUpdate($postArgs);
    }
}
