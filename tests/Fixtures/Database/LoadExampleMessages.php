<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\Message;
use App\Entity\MessageThread;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadExampleMessages extends AbstractFixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager): void {
        /** @noinspection PhpParamsInspection */
        $thread = new MessageThread(
            $this->getReference('user-zach'),
            $this->getReference('user-emma')
        );

        /* @noinspection PhpParamsInspection */
        $thread->addMessage(new Message(
            $thread,
            $this->getReference('user-zach'),
            'This is a message. There are many like it, but this one originates from a fixture.',
            '192.168.0.4'
        ));

        /* @noinspection PhpParamsInspection */
        $thread->addMessage(new Message(
            $thread,
            $this->getReference('user-emma'),
            'This is a reply to the message originating from a fixture.',
            '192.168.0.3'
        ));

        $manager->persist($thread);
        $manager->flush();
    }

    public function getDependencies(): array {
        return [LoadExampleUsers::class];
    }
}
