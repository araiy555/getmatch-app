<?php

namespace App\Tests\Message\Handler;

use App\DataTransfer\ImageManager;
use App\Entity\Image;
use App\Entity\Site;
use App\Message\Handler\DownloadSubmissionImageHandler;
use App\Message\NewSubmission;
use App\Repository\SiteRepository;
use App\Repository\SubmissionRepository;
use App\Tests\Fixtures\Factory\EntityFactory;
use App\Utils\UrlMetadataFetcherInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \App\Message\Handler\DownloadSubmissionImageHandler
 */
class DownloadSubmissionImageHandlerTest extends TestCase {
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var ImageManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imageManager;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var SiteRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sites;

    /**
     * @var SubmissionRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $submissions;

    /**
     * @var UrlMetadataFetcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlMetadataFetcher;

    /**
     * @var DownloadSubmissionImageHandler
     */
    private $handler;

    protected function setUp(): void {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->imageManager = $this->createMock(ImageManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->sites = $this->createMock(SiteRepository::class);
        $this->submissions = $this->createMock(SubmissionRepository::class);
        $this->urlMetadataFetcher = $this->createMock(UrlMetadataFetcherInterface::class);

        $this->handler = new DownloadSubmissionImageHandler(
            $this->entityManager,
            $this->imageManager,
            $this->logger,
            $this->sites,
            $this->submissions,
            $this->urlMetadataFetcher,
        );
    }

    public function testDoesNotDownloadIfDisabledInSiteSettings(): void {
        $this->sites
            ->expects($this->once())
            ->method('findCurrentSite')
            ->willReturnCallback(function () {
                $site = new Site();
                $site->setUrlImagesEnabled(false);

                return $site;
            });

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('Image downloading disabled in site settings');

        $this->urlMetadataFetcher
            ->expects($this->never())
            ->method('downloadRepresentativeImage');

        ($this->handler)(new NewSubmission(1));
    }

    public function testDownloadsImage(): void {
        $this->sites
            ->expects($this->once())
            ->method('findCurrentSite')
            ->willReturn(new Site());

        $submission = EntityFactory::makeSubmission();
        $submission->setUrl('http://www.example.com/foo');

        $this->submissions
            ->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($submission);

        $this->urlMetadataFetcher
            ->expects($this->once())
            ->method('downloadRepresentativeImage')
            ->with('http://www.example.com/foo')
            ->willReturn('/some/path/to/image.jpg');

        $image = new Image('image.jpg', hash('sha256', 'a'), 32, 32);

        $this->imageManager
            ->expects($this->once())
            ->method('findOrCreateFromFile')
            ->with('/some/path/to/image.jpg')
            ->willReturn($image);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        ($this->handler)(new NewSubmission(2));

        $this->assertSame($image, $submission->getImage());
    }
}
