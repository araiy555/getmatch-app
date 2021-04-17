<?php

namespace App\Tests\Fixtures\Utils;

use App\Utils\LanguageDetectorInterface;

/**
 * Language detector that always detects English.
 *
 * The language detection library used by Postmill gives unreliable results for
 * the short sentences commonly used in tests, hence this class.
 */
final class MockLanguageDetector implements LanguageDetectorInterface {
    public function detect(string $input, float &$confidence = null): ?string {
        return 'en';
    }
}
