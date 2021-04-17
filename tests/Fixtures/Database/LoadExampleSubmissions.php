<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\Tests\Fixtures\Utils\TimeMocker;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadExampleSubmissions extends AbstractFixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager): void {
        $i = 0;

        foreach ($this->provideSubmissions() as $data) {
            /** @var Forum $forum */
            $forum = $this->getReference('forum-'.$data['forum']);

            /** @var User $user */
            $user = $this->getReference('user-'.$data['user']);

            TimeMocker::mock(Submission::class, $data['timestamp']);
            $submission = new Submission(
                $data['title'],
                $data['url'],
                $data['body'],
                $forum,
                $user,
                $data['ip']
            );

            if ($data['trashed'] ?? false) {
                $submission->trash();
            }

            $this->addReference('submission-'.++$i, $submission);

            $manager->persist($submission);
        }

        $manager->flush();
    }

    private function provideSubmissions(): iterable {
        yield [
            'url' => 'http://www.example.com/some/thing',
            'title' => 'A submission with a URL and body',
            'body' => 'This is a body.',
            'ip' => '10.0.13.12',
            'timestamp' => new \DateTime('2017-03-03 03:03'),
            'user' => 'emma',
            'forum' => 'news',
        ];

        yield [
            'url' => 'http://www.example.org/another/thing',
            'title' => 'A submission with a URL',
            'body' => null,
            'ip' => '192.168.191.7',
            'timestamp' => new \DateTime('2017-04-03 03:01'),
            'user' => 'emma',
            'forum' => 'cats',
        ];

        yield [
            'url' => null,
            'title' => 'Submission with a body',
            'body' => "I'm bad at making stuff up.",
            'ip' => '127.8.9.0',
            'timestamp' => new \DateTime('2017-04-28 10:00'),
            'user' => 'zach',
            'forum' => 'cats',
        ];

        yield [
            'url' => null,
            'title' => 'Submission in the trash',
            'body' => 'anyone else writing tests during a pandemic',
            'ip' => null,
            'timestamp' => new \DateTime('2020-03-15 04:21:09'),
            'user' => 'zach',
            'forum' => 'cats',
            'trashed' => true,
        ];
    }

    public function getDependencies(): array {
        return [LoadExampleForums::class];
    }
}
