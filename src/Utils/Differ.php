<?php

namespace App\Utils;

use SebastianBergmann\Diff\Differ as BaseDiffer;
use SebastianBergmann\Diff\Output\DiffOutputBuilderInterface;

final class Differ implements DifferInterface {
    /**
     * @var DiffOutputBuilderInterface
     */
    private $outputBuilder;

    public function __construct(DiffOutputBuilderInterface $outputBuilder) {
        $this->outputBuilder = $outputBuilder;
    }

    public function diff(string $a, string $b): string {
        return (new BaseDiffer($this->outputBuilder))->diff($a, $b);
    }
}
