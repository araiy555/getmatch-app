<?php

namespace App\Tests\Fixtures\Utils;

use App\Utils\UrlMetadataFetcherInterface;

final class MockUrlMetadataFetcher implements UrlMetadataFetcherInterface {
    public function fetchTitle(string $url): ?string {
        return null;
    }

    public function downloadRepresentativeImage(string $url): ?string {
        return null;
    }
}
