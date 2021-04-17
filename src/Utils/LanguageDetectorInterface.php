<?php

namespace App\Utils;

interface LanguageDetectorInterface {
    public function detect(string $input, float &$confidence = null): ?string;
}
