<?php

namespace App\Doctrine;

use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class NamingStrategy extends UnderscoreNamingStrategy {
    /**
     * @var \Doctrine\Inflector\Inflector
     */
    private $inflector;

    public function __construct() {
        $this->inflector = InflectorFactory::create()->build();

        // remove after upgrading to doctrine 3.0
        parent::__construct(CASE_LOWER, true);
    }

    /**
     * Same as Doctrine's underscore naming strategy, except table names are
     * plural.
     *
     * @param string $className
     */
    public function classToTableName($className): string {
        return parent::classToTableName($this->inflector->pluralize($className));
    }
}
