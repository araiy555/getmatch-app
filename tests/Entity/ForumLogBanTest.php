<?php

namespace App\Tests\Entity;

use App\Entity\ForumBan;
use App\Entity\ForumLogBan;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\ForumLogBan
 */
class ForumLogBanTest extends TestCase {
    private function logEntry(ForumBan $ban = null): ForumLogBan {
        return new ForumLogBan($ban ?? $this->ban());
    }

    private function ban(): ForumBan {
        return new ForumBan(
            EntityFactory::makeForum(),
            EntityFactory::makeUser(),
            'a',
            true,
            EntityFactory::makeUser(),
            null,
        );
    }

    public function testGetBan(): void {
        $ban = $this->ban();

        $this->assertSame($ban, $this->logEntry($ban)->getBan());
    }

    public function testGetAction(): void {
        $this->assertSame('ban', $this->logEntry()->getAction());
    }
}
