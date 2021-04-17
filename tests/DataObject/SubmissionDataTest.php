<?php

namespace App\Tests\DataObject;

use App\DataObject\SubmissionData;
use App\Entity\Image;
use App\Entity\Submission;
use App\Entity\UserFlags;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DataObject\SubmissionData
 */
class SubmissionDataTest extends TestCase {
    private function data(): SubmissionData {
        return new SubmissionData();
    }

    public function testGetId(): void {
        $this->assertNull($this->data()->getId());
    }

    public function testGetTitle(): void {
        $this->assertNull($this->data()->getTitle());
    }

    public function testSetTitle(): void {
        $data = $this->data();

        $data->setTitle('some title');

        $this->assertSame('some title', $data->getTitle());
    }

    public function testGetUrl(): void {
        $this->assertNull($this->data()->getUrl());
    }

    public function testSetUrl(): void {
        $data = $this->data();

        $data->setUrl('http://www.example.com');

        $this->assertSame('http://www.example.com', $data->getUrl());
    }

    public function testGetBody(): void {
        $this->assertNull($this->data()->getBody());
    }

    public function testSetBody(): void {
        $data = $this->data();

        $data->setBody('some body');

        $this->assertSame('some body', $data->getBody());
    }

    public function testGetMediaType(): void {
        $this->assertSame(Submission::MEDIA_URL, $this->data()->getMediaType());
    }

    public function testSetMediaType(): void {
        $data = $this->data();

        $data->setMediaType(Submission::MEDIA_IMAGE);

        $this->assertSame(Submission::MEDIA_IMAGE, $data->getMediaType());
    }

    public function testGetCommentCount(): void {
        $this->assertSame(0, $this->data()->getCommentCount());
    }

    public function testGetTimestamp(): void {
        $this->assertNull($this->data()->getTimestamp());
    }

    public function testGetLastActive(): void {
        $this->assertNull($this->data()->getLastActive());
    }

    public function testGetVisibility(): void {
        $this->assertNull($this->data()->getVisibility());
    }

    public function testGetForum(): void {
        $this->assertNull($this->data()->getForum());
    }

    public function testSetForum(): void {
        $data = $this->data();
        $forum = EntityFactory::makeForum();

        $data->setForum($forum);

        $this->assertSame($forum, $data->getForum());
    }

    public function testGetUser(): void {
        $this->assertNull($this->data()->getUser());
    }

    public function testGetNetScore(): void {
        $this->assertSame(0, $this->data()->getNetScore());
    }

    public function testGetUpvotes(): void {
        $this->assertSame(0, $this->data()->getUpvotes());
    }

    public function testGetDownvotes(): void {
        $this->assertSame(0, $this->data()->getDownvotes());
    }

    public function testGetImage(): void {
        $this->assertNull($this->data()->getImage());
    }

    public function testSetImage(): void {
        $data = $this->data();
        $image = new Image('a.png', random_bytes(32), 16, 16);

        $data->setImage($image);

        $this->assertSame($image, $data->getImage());
    }

    public function testGetUserFlag(): void {
        $this->assertSame(UserFlags::FLAG_NONE, $this->data()->getUserFlag());
    }

    public function testSetUserFlag(): void {
        $data = $this->data();

        $data->setUserFlag(UserFlags::FLAG_ADMIN);

        $this->assertSame(UserFlags::FLAG_ADMIN, $data->getUserFlag());
    }

    public function testIsModerated(): void {
        $this->assertFalse($this->data()->isModerated());
    }

    public function testIsSticky(): void {
        $this->assertFalse($this->data()->isSticky());
    }


    public function testSetSticky(): void {
        $data = $this->data();

        $data->setSticky(true);

        $this->assertTrue($data->isSticky());
    }

    public function testGetEditedAt(): void {
        $this->assertNull($this->data()->getEditedAt());
    }

    public function testIsLocked(): void {
        $this->assertFalse($this->data()->isLocked());
    }

    public function testSetLocked(): void {
        $data = $this->data();

        $data->setLocked(true);

        $this->assertTrue($data->isLocked());
    }

    public function testGetMarkdownFields(): void {
        $this->assertCount(1, $this->data()->getMarkdownFields());
        $this->assertContains('body', $this->data()->getMarkdownFields());
    }

    public function testCreateFromSubmission(): void {
        $forum = EntityFactory::makeForum();
        $user = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission($forum, $user);
        $submission->setTitle('the title');
        $submission->setBody('the body');
        $submission->setUrl('the url');
        $submission->setEditedAt(new \DateTime('@1234567890'));
        $submission->setModerated(true);
        EntityFactory::makeComment(null, $submission);
        $r = new \ReflectionProperty(Submission::class, 'id');
        $r->setAccessible(true);
        $r->setValue($submission, 123);
        $r->setAccessible(false);

        $data = SubmissionData::createFromSubmission($submission);

        $this->assertSame(123, $data->getId());
        $this->assertSame($submission->getTitle(), $data->getTitle());
        $this->assertSame($submission->getBody(), $data->getBody());
        $this->assertSame($submission->getUrl(), $data->getUrl());
        $this->assertSame(1, $data->getCommentCount());
        $this->assertSame(1, $data->getNetScore());
        $this->assertSame(1, $data->getUpvotes());
        $this->assertSame(0, $data->getDownvotes());
        $this->assertSame($forum, $data->getForum());
        $this->assertSame($user, $data->getUser());
        $this->assertTrue($data->isModerated());
        $this->assertSame(Submission::VISIBILITY_VISIBLE, $data->getVisibility());
        $this->assertNotNull($data->getTimestamp());
        $this->assertEquals($submission->getTimestamp(), $data->getTimestamp());
        $this->assertNotNull($data->getEditedAt());
        $this->assertEquals($submission->getEditedAt(), $data->getEditedAt());
    }
}
