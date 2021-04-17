<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\WikiPage;
use App\Entity\WikiRevision;
use App\Tests\Fixtures\Utils\TimeMocker;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadExampleWiki extends AbstractFixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager): void {
        TimeMocker::mock(WikiRevision::class, new \DateTime('2019-04-20'));

        /** @noinspection PhpParamsInspection */
        $page = new WikiPage(
            'index',
            'This is the title',
            'and this is the body',
            $this->getReference('user-emma')
        );

        $manager->persist($page);
        $manager->flush();
    }

    public function getDependencies(): array {
        return [LoadExampleUsers::class];
    }
}
