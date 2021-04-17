<?php

namespace App\Serializer;

use App\DataObject\SubmissionData;
use App\Utils\SluggerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class SubmissionDataNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface {
    use NormalizerAwareTrait;

    public const NORMALIZED_MARKER = 'submission_data_normalized';

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var SluggerInterface
     */
    private $slugger;

    public function __construct(CacheManager $cacheManager, SluggerInterface $slugger) {
        $this->cacheManager = $cacheManager;
        $this->slugger = $slugger;
    }

    public function normalize($object, string $format = null, array $context = []): array {
        $context[self::NORMALIZED_MARKER][spl_object_id($object)] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        if (\array_key_exists('image', $data)) {
            $image = $object->getImage();

            foreach (['1x', '2x'] as $size) {
                if ($image) {
                    $url = $this->cacheManager->generateUrl(
                        $image,
                        "submission_thumbnail_{$size}"
                    );
                }

                $data["thumbnail_{$size}"] = $url ?? null;
            }
        }

        if (array_intersect($context['groups'] ?? [], ['submission:read', 'abbreviated_relations'])) {
            $data['slug'] = $this->slugger->slugify($object->getTitle());
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool {
        return $data instanceof SubmissionData &&
            empty($context[self::NORMALIZED_MARKER][spl_object_id($data)]);
    }
}
