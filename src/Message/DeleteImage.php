<?php

namespace App\Message;

class DeleteImage {
    /**
     * @var string[]
     */
    private $fileNames;

    public function __construct(string ...$fileNames) {
        $this->fileNames = $fileNames;
    }

    public function getFileNames(): array {
        return $this->fileNames;
    }
}
