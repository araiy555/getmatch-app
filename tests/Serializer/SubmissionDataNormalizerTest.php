<?php

namespace App\Tests\Serializer;

use App\DataObject\SubmissionData;
use App\Entity\Image;
use App\Serializer\SubmissionDataNormalizer;
use App\Utils\SluggerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @covers \App\Serializer\SubmissionDataNormalizer
 */
class SubmissionDataNormalizerTest extends TestCase {
    /**
     * @var SluggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $slugger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CacheManager
     */
    private $cacheManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|NormalizerInterface
     */
    private $decorated;

    /**
     * @var SubmissionDataNormalizer
     */
    private $normalizer;

    protected function setUp(): void {
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->decorated = $this->createMock(NormalizerInterface::class);
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->normalizer = new SubmissionDataNormalizer($this->cacheManager, $this->slugger);
        $this->normalizer->setNormalizer($this->decorated);
    }

    public function testSupportsSubmissionData(): void {
        $data = new SubmissionData();

        $this->assertTrue($this->normalizer->supportsNormalization($data));
    }

    public function testAddsImagePathsToNormalizedData(): void {
        $this->cacheManager
            ->expects($this->exactly(2))
            ->method('generateUrl')
            ->withConsecutive(
                ['foo.png', 'submission_thumbnail_1x'],
                ['foo.png', 'submission_thumbnail_2x']
            )
            ->willReturnOnConsecutiveCalls(
                'http://localhost/1x/foo.png',
                'http://localhost/2x/foo.png'
            );

        $data = new SubmissionData();
        $data->setImage(new Image('foo.png', random_bytes(32), null, null));

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($data)
            ->willReturn(['image' => 'foo.png']);

        $normalized = $this->normalizer->normalize($data);

        $this->assertSame('http://localhost/1x/foo.png', $normalized['thumbnail_1x']);
        $this->assertSame('http://localhost/2x/foo.png', $normalized['thumbnail_2x']);
    }

    /**
     * @dataProvider provideSlugGroups
     */
    public function testAddsSlug(array $groups): void {
        $data = new SubmissionData();
        $data->setTitle('some title');

        $this->slugger
            ->expects($this->once())
            ->method('slugify')
            ->with('some title')
            ->willReturn('slugged-title');

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($data)
            ->willReturn([]);

        $normalized = $this->normalizer->normalize($data, null, ['groups' => $groups]);

        $this->assertArrayHasKey('slug', $normalized);
        $this->assertSame('slugged-title', $normalized['slug']);
    }

    public function provideSlugGroups(): \Generator {
        yield [['submission:read']];
        yield [['abbreviated_relations']];
        yield [['submission:read', 'abbreviated_relations']];
    }

    public function testDoesNotAddSlugWithoutCorrectGroup(): void {
        $data = new SubmissionData();
        $data->setTitle('some title');

        $this->slugger
            ->expects($this->never())
            ->method('slugify');

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($data)
            ->willReturn([]);

        $normalized = $this->normalizer->normalize($data);

        $this->assertArrayNotHasKey('slug', $normalized);
    }
}
