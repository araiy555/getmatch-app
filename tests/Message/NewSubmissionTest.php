<?php

namespace App\Tests\Message;

use App\Message\NewSubmission;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Message\NewSubmission
 */
class NewSubmissionTest extends TestCase {
    public function testConstructWithSubmissionAndGetId(): void {
        $submission = EntityFactory::makeSubmission();

        $r = (new \ReflectionObject($submission))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($submission, 123);
        $r->setAccessible(false);

        $message = new NewSubmission($submission);

        $this->assertSame(123, $message->getSubmissionId());
    }

    public function testConstructWithSubmissionIdAndGetId(): void {
        $message = new NewSubmission(321);

        $this->assertSame(321, $message->getSubmissionId());
    }

    public function testThrowsWhenConstructorIsGivenSubmissionWithId(): void {
        $this->expectException(\InvalidArgumentException::class);

        new NewSubmission(EntityFactory::makeSubmission());
    }

    public function testThrowsWhenConstructorIsGivenInvalidParameter(): void {
        $this->expectException(\TypeError::class);

        /** @noinspection PhpParamsInspection */
        new NewSubmission(EntityFactory::makeComment());
    }
}
