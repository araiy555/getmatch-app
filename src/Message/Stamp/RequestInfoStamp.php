<?php

namespace App\Message\Stamp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Message stamp that contains some info from the request.
 */
final class RequestInfoStamp implements StampInterface {
    /**
     * @var RequestContext
     */
    private $requestContext;

    /**
     * @var string[]
     */
    private $clientIps;

    /**
     * @var string
     */
    private $locale;

    public static function createFromRequest(Request $request): self {
        $self = new self();
        $self->requestContext = (new RequestContext())->fromRequest($request);
        $self->clientIps = $request->getClientIps();
        $self->locale = $request->getLocale();

        return $self;
    }

    private function __construct() {
    }

    public function getRequestContext(): RequestContext {
        return $this->requestContext;
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function getClientIp(): string {
        return $this->clientIps[0];
    }

    /**
     * @return string[]
     */
    public function getClientIps(): array {
        return $this->clientIps;
    }
}
