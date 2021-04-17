<?php

namespace App\Utils\Exception;

class ImageDownloadTooLargeException extends \RuntimeException {
    public function __construct(int $max, int $size) {
        parent::__construct(sprintf(
            'Image download was too large (%d/%d bytes)',
            $size,
            $max,
        ));
    }
}
