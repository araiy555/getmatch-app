<?php

namespace App\Message\Handler;

use App\DataTransfer\ImageManager;
use App\Entity\Submission;
use App\Message\NewSubmission;
use App\Repository\SiteRepository;
use App\Repository\SubmissionRepository;
use App\Utils\UrlMetadataFetcherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class DownloadSubmissionImageHandler implements MessageHandlerInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SiteRepository
     */
    private $sites;

    /**
     * @var SubmissionRepository
     */
    private $submissions;

    /**
     * @var UrlMetadataFetcherInterface
     */
    private $urlMetadataFetcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        ImageManager $imageManager,
        LoggerInterface $logger,
        SiteRepository $sites,
        SubmissionRepository $submissions,
        UrlMetadataFetcherInterface $urlMetadataFetcher
    ) {
        $this->entityManager = $entityManager;
        $this->imageManager = $imageManager;
        $this->logger = $logger;
        $this->sites = $sites;
        $this->submissions = $submissions;
        $this->urlMetadataFetcher = $urlMetadataFetcher;
    }

    public function __invoke(NewSubmission $message): void {
        if (!$this->sites->findCurrentSite()->isUrlImagesEnabled()) {
            $this->logger->debug('Image downloading disabled in site settings');

            return;
        }

        $id = $message->getSubmissionId();
        $submission = $this->submissions->find($id);

        if (!$submission instanceof Submission) {
            throw new UnrecoverableMessageHandlingException(
                "Submission with ID {$id} not found"
            );
        }

        if (
            $submission->getMediaType() !== Submission::MEDIA_URL ||
            !$submission->getUrl()
        ) {
            return;
        }

        $url = $submission->getUrl();
        $fileName = $this->urlMetadataFetcher->downloadRepresentativeImage($url);

        if (!$fileName) {
            return;
        }

        $image = $this->imageManager->findOrCreateFromFile($fileName);
        $submission->setImage($image);

        $this->entityManager->flush();
    }
}
