<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\BundledTheme;
use App\Entity\CssTheme;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class ThemeFixtures extends AbstractFixture {
    public function load(ObjectManager $manager): void {
        $manager->persist(new BundledTheme('Postmill', 'postmill'));
        $manager->persist(new BundledTheme('Postmill Classic', 'postmill-classic'));
        $manager->persist(new CssTheme('My Custom Theme', ':root { --bg-page: #0aa }'));
        $manager->flush();
    }
}
