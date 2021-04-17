<?php

namespace App\Utils;

final class Slugger implements SluggerInterface {
    public function slugify(
        string $input,
        int $maxLength = self::DEFAULT_MAX_LENGTH
    ): string {
        $input = mb_strtolower($input, 'UTF-8');

        $words = preg_split('/[^\w]+/u', $input, -1, PREG_SPLIT_NO_EMPTY);
        $slug = '';
        $len = 0;

        foreach ($words as $word) {
            $add = $len > 0 ? "-$word" : $word;
            $len += grapheme_strlen($add);

            if ($len > $maxLength) {
                break;
            }

            $slug .= $add;
        }

        if ($slug === '') {
            return '-';
        }

        return $slug;
    }
}
