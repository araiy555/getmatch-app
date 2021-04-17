<?php

namespace App\Tests\Repository;

use App\Entity\Image;
use App\Entity\Submission;
use App\Repository\ImageRepository;
use App\Repository\SubmissionRepository;

/**
 * @covers \App\Repository\ImageRepository
 */
class ImageRepositoryTest extends RepositoryTestCase {
    /**
     * @var ImageRepository
     */
    private $repository;

    protected function setUp(): void {
        parent::setUp();

        $this->repository = $this->entityManager->getRepository(Image::class);
    }

    public function testCanFilterByOrphanedImages(): void {
        $orphanedImage = new Image('a.png', hash('sha256', 'a'), null, null);
        $this->entityManager->persist($orphanedImage);

        /** @var Submission $submission */
        $submission = self::$container
            ->get(SubmissionRepository::class)
            ->findOneBy(['url' => null]);

        $usedImage = new Image('b.png', hash('sha256', 'b'), null, null);
        $submission->setImage($usedImage);

        $this->entityManager->flush();

        $this->assertEquals(
            [$orphanedImage],
            $this->repository->filterOrphaned([$orphanedImage, $usedImage]),
        );
    }
}
