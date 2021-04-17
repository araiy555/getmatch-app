<?php

namespace App\Utils;

interface DifferInterface {
    public function diff(string $a, string $b): string;
}
