<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\WikiPage;
use App\Entity\WikiRevision;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\WikiPage
 */
class WikiPageTest extends TestCase {
    private function wikiPage(User $user = null): WikiPage {
        $user = $user ?? EntityFactory::makeUser();

        return new WikiPage('The-Path', 'The title', 'The body', $user);
    }

    public function testGetId(): void {
        $this->assertNull($this->wikiPage()->getId());
    }

    public function testGetIdWithPropertySet(): void {
        $wikiPage = $this->wikiPage();
        $r = (new \ReflectionClass(WikiPage::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($wikiPage, 123);
        $r->setAccessible(false);

        $this->assertSame(123, $wikiPage->getId());
    }

    public function testGetPath(): void {
        $this->assertSame('The-Path', $this->wikiPage()->getPath());
    }

    public function testGetNormalizedPath(): void {
        $this->assertSame('the_path', $this->wikiPage()->getNormalizedPath());
    }

    public function testSetPath(): void {
        $wikiPage = $this->wikiPage();

        $wikiPage->setPath('The-Other-Path');

        $this->assertSame('The-Other-Path', $wikiPage->getPath());
        $this->assertSame('the_other_path', $wikiPage->getNormalizedPath());
    }

    public function testGetRevisions(): void {
        $user = EntityFactory::makeUser();
        $wikiPage = $this->wikiPage($user);

        $revisions = $wikiPage->getRevisions();

        $this->assertCount(1, $this->wikiPage()->getRevisions());
        $this->assertContainsOnlyInstancesOf(WikiRevision::class, $revisions);
        $this->assertArrayHasKey(0, $revisions);
        $this->assertSame($wikiPage, $revisions[0]->getPage());
        $this->assertSame('The title', $revisions[0]->getTitle());
        $this->assertSame('The body', $revisions[0]->getBody());
        $this->assertSame($user, $revisions[0]->getUser());
    }

    public function testGetLatestRevision(): void {
        $user = EntityFactory::makeUser();
        $wikiPage = $this->wikiPage($user);

        $revision = $wikiPage->getLatestRevision();

        $this->assertSame($wikiPage, $revision->getPage());
        $this->assertSame('The title', $revision->getTitle());
        $this->assertSame('The body', $revision->getBody());
        $this->assertSame($user, $revision->getUser());
    }

    public function testAddRevision(): void {
        $user = EntityFactory::makeUser();
        $wikiPage = $this->wikiPage();
        $revision = new WikiRevision($wikiPage, 'New title', 'New body', $user);

        $wikiPage->addRevision($revision);

        $this->assertCount(2, $wikiPage->getRevisions());
        $this->assertArrayHasKey(1, $wikiPage->getRevisions());
        $this->assertSame($revision, $wikiPage->getRevisions()[1]);
        $this->assertSame($revision, $wikiPage->getLatestRevision());
    }

    public function testGetPaginatedRevisions(): void {
        $pager = $this->wikiPage()->getPaginatedRevisions(1, 30);

        $this->assertSame(1, $pager->getCurrentPage());
        $this->assertSame(30, $pager->getMaxPerPage());
        $this->assertCount(1, $pager);
        $this->assertContainsOnlyInstancesOf(WikiRevision::class, $pager);
    }

    public function testIsLocked(): void {
        $this->assertFalse($this->wikiPage()->isLocked());
    }

    public function testSetLocked(): void {
        $wikiPage = $this->wikiPage();

        $wikiPage->setLocked(true);

        $this->assertTrue($wikiPage->isLocked());
    }
}
