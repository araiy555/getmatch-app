<?php

namespace App\Serializer;

use App\DataObject\SubmissionData;
use App\Entity\Submission;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SubmissionNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface,
    CacheableSupportsMethodInterface
{
    use NormalizerAwareTrait;

    public function normalize($object, string $format = null, array $context = []): array {
        $object = SubmissionData::createFromSubmission($object);

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, string $format = null): bool {
        return $data instanceof Submission;
    }

    public function hasCacheableSupportsMethod(): bool {
        return true;
    }
}
