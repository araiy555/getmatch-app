<?php

namespace App\Utils;

use App\Utils\Exception\ImageDownloadTooLargeException;
use Embed\Embed;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UrlMetadataFetcher implements UrlMetadataFetcherInterface {
    private const MAX_IMAGE_BYTES = 4000000;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        HttpClientInterface $imageDownloadingClient,
        LoggerInterface $logger,
        ValidatorInterface $validator
    ) {
        $this->httpClient = $imageDownloadingClient;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    public function fetchTitle(string $url): ?string {
        // TODO: remove dependency on this library
        return Embed::create($url)->getTitle();
    }

    public function downloadRepresentativeImage(string $url): ?string {
        // TODO: remove dependency on this library
        $url = Embed::create($url)->getImage();

        if (!\is_string($url)) {
            return null;
        }

        $tempFile = @tempnam(sys_get_temp_dir(), 'postmill-downloads');

        if (!\is_string($tempFile)) {
            throw new \RuntimeException('Failed to create temporary directory');
        }

        $fh = fopen($tempFile, 'wb');

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'image/jpeg, image/gif, image/png',
                ],
                'on_progress' => function (int $downloaded, int $downloadSize) {
                    if (
                        $downloaded > self::MAX_IMAGE_BYTES ||
                        $downloadSize > self::MAX_IMAGE_BYTES
                    ) {
                        throw new ImageDownloadTooLargeException(
                            self::MAX_IMAGE_BYTES,
                            max($downloadSize, $downloaded),
                        );
                    }
                },
            ]);

            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($fh, $chunk->getContent());
            }

            fclose($fh);
        } catch (\Throwable $e) {
            fclose($fh);
            unlink($tempFile);

            if ($e->getPrevious() instanceof ImageDownloadTooLargeException) {
                $this->logger->debug($e->getMessage());

                return null;
            }

            throw $e;
        }

        $errors = $this->validator->validate($tempFile, new Image([
            'detectCorrupted' => true,
        ]));

        if (\count($errors) > 0) {
            unlink($tempFile);

            return null;
        }

        return $tempFile;
    }
}
