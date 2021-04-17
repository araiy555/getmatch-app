<?php

namespace App\Serializer;

use App\DataObject\ForumData;
use App\Entity\Forum;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ForumNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface,
    CacheableSupportsMethodInterface
{
    use NormalizerAwareTrait;

    public function normalize($object, string $format = null, array $context = []): array {
        $object = ForumData::createFromForum($object);

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, string $format = null): bool {
        return $data instanceof Forum;
    }

    public function hasCacheableSupportsMethod(): bool {
        return true;
    }
}
