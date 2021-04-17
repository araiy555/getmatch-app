<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\User;
use App\Tests\Fixtures\Utils\TimeMocker;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class LoadExampleUsers extends AbstractFixture {
    public function load(ObjectManager $manager): void {
        foreach ($this->provideUsers() as $data) {
            TimeMocker::mock(User::class, $data['created']);

            // use plaintext passwords in fixtures to speed up tests
            $user = new User($data['username'], $data['password']);
            $user->setAdmin($data['admin']);
            $user->setEmail($data['email']);
            $user->setRegistrationIp($data['registration_ip'] ?? null);

            $this->addReference('user-'.$data['username'], $user);

            $manager->persist($user);
        }

        $manager->flush();
    }

    private function provideUsers(): iterable {
        yield [
            'username' => 'emma',
            'password' => 'goodshit',
            'created' => new \DateTime('2017-01-01T12:12:12+00:00'),
            'email' => 'emma@example.com',
            'admin' => true,
        ];

        yield [
            'username' => 'zach',
            'password' => 'example2',
            'created' => new \DateTime('2017-01-02T12:12:12+00:00'),
            'email' => 'zach@example.com',
            'admin' => false,
            'registration_ip' => '192.168.0.55',
        ];

        yield [
            'username' => 'third',
            'password' => 'example3',
            'created' => new \DateTime('2017-01-03T12:12:12+00:00'),
            'email' => 'third@example.net',
            'admin' => false,
        ];
    }
}
