<?php

namespace App\Entity\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class BannedFromForumException extends \DomainException implements HttpExceptionInterface {
    public function __construct() {
        parent::__construct('User is banned from forum');
    }

    public function getStatusCode(): int {
        return 403;
    }

    public function getHeaders(): array {
        return [];
    }
}
