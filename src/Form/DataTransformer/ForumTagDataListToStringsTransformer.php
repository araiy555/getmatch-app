<?php

namespace App\Form\DataTransformer;

use App\DataObject\ForumTagData;
use Symfony\Component\Form\DataTransformerInterface;

class ForumTagDataListToStringsTransformer implements DataTransformerInterface {
    /**
     * @param iterable|null $value
     */
    public function transform($value): array {
        if (\is_iterable($value)) {
            foreach ($value as $tag) {
                \assert($tag instanceof ForumTagData);

                $tagStrings[] = $tag->getName();
            }
        } elseif ($value !== null) {
            throw new \TypeError(sprintf(
                '$value must be iterable or NULL, %s given',
                get_debug_type($value)
            ));
        }

        return $tagStrings ?? [];
    }

    /**
     * @param iterable|null $value
     */
    public function reverseTransform($value): array {
        if (\is_iterable($value)) {
            foreach ($value as $tagString) {
                \assert(\is_string($tagString));

                $tags[] = $tag = new ForumTagData();
                $tag->setName($tagString);
            }
        } elseif ($value !== null) {
            throw new \TypeError(sprintf(
                '$value must be iterable or NULL, %s given',
                get_debug_type($value)
            ));
        }

        return $tags ?? [];
    }
}
