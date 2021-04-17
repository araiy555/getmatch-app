<?php

namespace App\Message\Middleware;

use App\Message\Stamp\RequestInfoStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslatorLocaleMiddleware implements MiddlewareInterface {
    /**
     * @var TranslatorInterface|LocaleAwareInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator) {
        if (!$translator instanceof LocaleAwareInterface) {
            throw new \InvalidArgumentException(
                '$translator must implement '.LocaleAwareInterface::class
            );
        }

        $this->translator = $translator;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope {
        $requestInfo = $envelope->last(RequestInfoStamp::class);
        \assert(!$requestInfo || $requestInfo instanceof RequestInfoStamp);

        if ($requestInfo) {
            $defaultLocale = $this->translator->getLocale();
            $this->translator->setLocale($requestInfo->getLocale());
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            if ($requestInfo) {
                /* @noinspection PhpUndefinedVariableInspection */
                $this->translator->setLocale($defaultLocale);
            }
        }
    }
}
