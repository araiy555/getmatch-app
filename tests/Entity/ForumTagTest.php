<?php

namespace App\Tests\Entity;

use App\Entity\ForumTag;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\ForumTag
 */
class ForumTagTest extends TestCase {
    private function tag(): ForumTag {
        return new ForumTag('Tag', 'Very cool tag');
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $fields = $this->tag()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetName(): void {
        $this->assertSame('Tag', $this->tag()->getName());
    }

    public function testGetNormalizedName(): void {
        $this->assertSame('tag', $this->tag()->getNormalizedName());
    }

    public function testGetDescription(): void {
        $this->assertSame('Very cool tag', $this->tag()->getDescription());
    }

    public function testSetDescription(): void {
        $tag = $this->tag();

        $tag->setDescription('very long description');

        $this->assertSame('very long description', $tag->getDescription());
    }

    public function testHasForum(): void {
        $forum = EntityFactory::makeForum();
        $tag = $this->tag();
        $tag->addForum($forum);

        $this->assertTrue($tag->hasForum($forum));
        $this->assertFalse($tag->hasForum(EntityFactory::makeForum()));
    }

    /**
     * @dataProvider provideTagsWithForums
     */
    public function testGetForums(ForumTag $tag, array $forums): void {
        $this->assertSame($forums, $tag->getForums());
    }

    /**
     * @dataProvider provideTagsWithForums
     */
    public function testGetForumCount(ForumTag $tag, array $forums): void {
        $this->assertSame(\count($forums), $tag->getForumCount());
    }

    public function provideTagsWithForums(): \Generator {
        yield 'no forums' => [$this->tag(), []];

        $forum1 = EntityFactory::makeForum();
        $forum1->setName('a');
        $forum2 = EntityFactory::makeForum();
        $forum2->setName('z');

        $tag = $this->tag();
        $tag->addForum($forum1);
        $tag->addForum($forum2);
        yield '2 forums, natural order' => [$tag, [$forum1, $forum2]];

        $tag = $this->tag();
        $tag->addForum($forum2);
        $tag->addForum($forum1);
        yield '2 forums, reverse order' => [$tag, [$forum1, $forum2]];
    }

    public function testAddForum(): void {
        $forum = EntityFactory::makeForum();
        $tag = $this->tag();

        $tag->addForum($forum);

        $this->assertTrue($tag->hasForum($forum));
        $this->assertTrue($forum->hasTag($tag));
    }

    public function testRemoveForum(): void {
        $forum = EntityFactory::makeForum();
        $tag = $this->tag();
        $tag->addForum($forum);

        $tag->removeForum($forum);

        $this->assertFalse($tag->hasForum($forum));
    }
}
