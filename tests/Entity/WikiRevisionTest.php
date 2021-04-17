<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\WikiPage;
use App\Entity\WikiRevision;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\WikiRevision
 */
class WikiRevisionTest extends TestCase {
    public function testIdIsUuidV4(): void {
        $fields = $this->wikiRevision()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetTitle(): void {
        $this->assertSame('The title', $this->wikiRevision()->getTitle());
    }

    public function testGetBody(): void {
        $this->assertSame('The body', $this->wikiRevision()->getBody());
    }

    public function testGetPage(): void {
        $user = EntityFactory::makeUser();
        $page = new WikiPage('The-Path', 'The title', 'The body', $user);

        $this->assertSame($page, $this->wikiRevision($page)->getPage());
    }

    public function testGetUser(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->wikiRevision(null, $user)->getUser());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(
            time(),
            $this->wikiRevision()->getTimestamp()->getTimestamp(),
        );
    }

    private function wikiRevision(WikiPage $page = null, User $user = null): WikiRevision {
        $user = $user ?? EntityFactory::makeUser();
        $page = $page ?? new WikiPage('The-Path', 'The title', 'The body', $user);

        return $page->getLatestRevision();
    }
}
