<?php

namespace App\Serializer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class EntityRetrievingDenormalizer implements DenormalizerInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @param mixed $data
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): ?object {
        return $this->entityManager->find($type, $data);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool {
        return is_scalar($data) && strpos($type, 'App\Entity') === 0 && substr_count($type, '\\') === 2;
    }
}
