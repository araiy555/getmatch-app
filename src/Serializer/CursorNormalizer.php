<?php

namespace App\Serializer;

use PagerWave\CursorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CursorNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface,
    CacheableSupportsMethodInterface
{
    use NormalizerAwareTrait;

    public function normalize($object, string $format = null, array $context = []): array {
        \assert($object instanceof CursorInterface);

        $entries = iterator_to_array($object);

        return array_filter([
            'entries' => $this->normalizer->normalize($entries, $format, $context),
            'nextPage' => $object->hasNextPage() ? $object->getNextPageUrl() : null,
        ]);
    }

    public function supportsNormalization($data, string $format = null): bool {
        return $data instanceof CursorInterface;
    }

    public function hasCacheableSupportsMethod(): bool {
        return true;
    }
}
