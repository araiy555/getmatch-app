<?php

namespace App\Entity\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class SubmissionLockedException extends \DomainException implements HttpExceptionInterface {
    public function __construct() {
        parent::__construct('The submission is locked');
    }

    public function getStatusCode(): int {
        return 403;
    }

    public function getHeaders(): array {
        return [];
    }
}
