<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\Forum;
use App\Entity\ForumTag;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ForumTagFixtures extends AbstractFixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager): void {
        $tag1 = new ForumTag('pets', 'fluffy pets and stuff');
        $tag2 = new ForumTag('humans', 'not fluffy or stuff');

        $catsForum = $this->getReference('forum-cats');
        \assert($catsForum instanceof Forum);

        $newsForum = $this->getReference('forum-news');
        \assert($newsForum instanceof Forum);

        $catsForum->addTags($tag1);
        $newsForum->addTags($tag1, $tag2);

        $manager->persist($tag1);
        $manager->persist($tag2);
        $manager->flush();
    }

    public function getDependencies(): array {
        return [LoadExampleForums::class];
    }
}
