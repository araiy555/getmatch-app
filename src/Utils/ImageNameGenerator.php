<?php

namespace App\Utils;

use App\Utils\Exception\ImageNameGenerationFailedException;
use Symfony\Component\Mime\MimeTypesInterface;

final class ImageNameGenerator implements ImageNameGeneratorInterface {
    /**
     * @var MimeTypesInterface
     */
    private $mimeTypes;

    public function __construct(MimeTypesInterface $mimeTypes) {
        $this->mimeTypes = $mimeTypes;
    }

    public function generateName(string $path): string {
        $hash = hash_file('sha256', $path);

        $mimeType = $this->mimeTypes->guessMimeType($path);

        if (!$mimeType) {
            throw new ImageNameGenerationFailedException(
                "Couldn't guess MIME type of image",
            );
        }

        $ext = array_values($this->mimeTypes->getExtensions($mimeType))[0]
            ?? null;

        if (!$ext) {
            throw new ImageNameGenerationFailedException(
                "Couldn't guess extension of image"
            );
        }

        return sprintf('%s.%s', $hash, $ext);
    }
}
