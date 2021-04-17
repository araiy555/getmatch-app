<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class TagArrayToStringTransformer implements DataTransformerInterface {
    /**
     * @param string[]|null $value
     */
    public function transform($value): string {
        if (\is_array($value)) {
            natcasesort($value);
        } elseif ($value !== null) {
            throw new \TypeError(sprintf(
                '$value must be array or NULL, %s given',
                get_debug_type($value)
            ));
        }

        return implode(', ', $value ?? []);
    }

    /**
     * @param string|null $value
     */
    public function reverseTransform($value): array {
        if (\is_string($value)) {
            return preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        }

        if ($value !== null) {
            throw new \TypeError(sprintf(
                '$value must be string or NULL, %s given',
                get_debug_type($value)
            ));
        }

        return [];
    }
}
