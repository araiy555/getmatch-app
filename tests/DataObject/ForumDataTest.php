<?php

namespace App\Tests\DataObject;

use App\DataObject\ForumData;
use App\DataObject\ForumTagData;
use App\Entity\Forum;
use App\Entity\ForumTag;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DataObject\ForumData
 */
class ForumDataTest extends TestCase {
    public function testTagsNotGeneratedWhenCreatingFromForum(): void {
        $forum = $this->createMock(Forum::class);
        $forum->expects($this->never())
            ->method('getTags');

        ForumData::createFromForum($forum);
    }

    public function testTagsGeneratedWhenCreatingFromForumAndCallingGetTags(): void {
        $forum = $this->createMock(Forum::class);
        $forum->expects($this->once())
            ->method('getTags')
            ->willReturn([new ForumTag('foo'), new ForumTag('bar')]);

        $tags = ForumData::createFromForum($forum)->getTags();

        $this->assertEqualsCanonicalizing(
            ['foo', 'bar'],
            array_map(static function (ForumTagData $data) {
                return $data->getName();
            }, $tags)
        );
    }
}
