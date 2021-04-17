<?php

namespace App\Tests\Utils;

use App\Utils\Slugger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\Slugger
 */
class SluggerTest extends TestCase {
    /**
     * @dataProvider inputProvider
     */
    public function testCanSlugifyInputs(string $expected, string $input): void {
        $this->assertSame($expected, (new Slugger())->slugify($input));
    }

    public function inputProvider(): iterable {
        yield ['feature-request-title-excerpt-in-the-url', '[Feature Request] Title excerpt in the URL'];
        yield ['free-market-capitalism-summed-up-by-one-gif', 'Free market capitalism summed up by one gif'];
        yield ['socialism-isn-t-capitalism', 'Socialism isn\'t capitalism'];
        yield ['商品は先ず-外界の一対象である-即ちその諸性質によって-人類の何らかの欲望を満たす一つの物である-この欲望の性質いかん', '商品は先ず、外界の一対象である。即ちその諸性質によって、人類の何らかの欲望を満たす一つの物である。この欲望の性質いかん、即ちそれが胃腑から起こるか、または空想から起こるかは、問題の上に何らの変化をも与えるものではない。'];
        yield ['run-bin-console-server-run-to-start-the-application', 'Run `bin/console server:run` to start the application.'];
        yield ['one-two-three-four-five-six-seven-eight-nine-ten-eleven', 'one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen'];
        yield [str_repeat('a', 60), str_repeat('a', 60)];
        yield ['-', str_repeat('a', 61)];
        yield ['foo-bar', 'foo∑bar'];
        yield ['-', '[]!@#$%^&*(){}/'];
        yield ['a-a', 'a🔥a'];
        yield ['-', ''];
    }
}
