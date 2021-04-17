<?php

namespace App\Utils;

use LanguageDetection\Language;

final class LanguageDetector implements LanguageDetectorInterface {
    /**
     * @var Language
     */
    private $language;

    public function __construct(Language $language) {
        $this->language = $language;
    }

    public function detect(string $input, float &$confidence = null): ?string {
        if ($input === '') {
            return null;
        }

        $results = $this->language->detect($input)->bestResults()->close();
        $language = array_key_first($results);
        $confidence = $results[$language] ?? null;

        return $language;
    }
}
