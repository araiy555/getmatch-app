<?php

namespace App\Entity\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class BadVoteChoiceException extends \DomainException implements HttpExceptionInterface {
    /**
     * @param mixed $given
     */
    public function __construct($given) {
        parent::__construct(sprintf(
            'Bad vote choice (expected one of Votable::VOTE_* constants, given %s)',
            is_scalar($given) ? var_export($given, true) : \gettype($given)
        ));
    }

    public function getStatusCode(): int {
        return 400;
    }

    public function getHeaders(): array {
        return [];
    }
}
