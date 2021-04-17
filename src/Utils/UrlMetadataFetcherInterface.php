<?php

namespace App\Utils;

interface UrlMetadataFetcherInterface {
    /**
     * Fetches the title of the URL, or returns NULL.
     */
    public function fetchTitle(string $url): ?string;

    /**
     * Downloads a representative image for the URL, and either stores it at the
     * temporary path being returned, or returns NULL.
     */
    public function downloadRepresentativeImage(string $url): ?string;
}
