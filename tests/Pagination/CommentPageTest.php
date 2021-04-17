<?php

namespace App\Tests\Pagination;

use App\Pagination\CommentPage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Pagination\CommentPage
 */
class CommentPageTest extends TestCase {
    /**
     * @var CommentPage
     */
    private $page;

    protected function setUp(): void {
        $this->page = new CommentPage();
    }

    public function testDefinition(): void {
        $this->assertSame(['timestamp', 'id'], $this->page->getFieldNames());
        $this->assertTrue($this->page->isFieldDescending('timestamp'));
        $this->assertTrue($this->page->isFieldDescending('id'));
        $this->assertTrue($this->page->isFieldValid('timestamp', '2020-02-02T02:02:02+00:00'));
        $this->assertTrue($this->page->isFieldValid('id', '1241241'));
    }

    /**
     * @dataProvider provideInvalidFieldValuePairs
     */
    public function testInvalidFieldValuePairs(string $field, string $value): void {
        $this->assertFalse($this->page->isFieldValid($field, $value));
    }

    public function provideInvalidFieldValuePairs(): \Generator {
        yield ['timestamp', '2020-1332T02:02:02+00:00'];
        yield ['id', '4.2069'];
        yield ['id', \PHP_INT_MAX.'1'];
        yield ['a', 'wahat'];
    }
}
