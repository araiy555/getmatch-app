<?php

namespace App\Utils;

use App\Utils\Exception\ImageNameGenerationFailedException;

interface ImageNameGeneratorInterface {
    /**
     * @throws ImageNameGenerationFailedException
     */
    public function generateName(string $path): string;
}
