<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\BadPhrase;
use App\Tests\Fixtures\Utils\TimeMocker;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class BadPhraseFixtures extends AbstractFixture {
    public function load(ObjectManager $manager): void {
        TimeMocker::mock(BadPhrase::class, new \DateTime('yesterday'));
        $manager->persist(new BadPhrase('o...n p.g', BadPhrase::TYPE_REGEX));

        TimeMocker::mock(BadPhrase::class, new \DateTime('today'));
        $manager->persist(new BadPhrase('orson pig', BadPhrase::TYPE_TEXT));

        $manager->flush();
    }
}
