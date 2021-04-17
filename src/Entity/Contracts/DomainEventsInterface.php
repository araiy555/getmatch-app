<?php

namespace App\Entity\Contracts;

use Symfony\Contracts\EventDispatcher\Event;

interface DomainEventsInterface {
    public function onCreate(): Event;

    /**
     * @param static $previous
     */
    public function onUpdate($previous): Event;

    public function onDelete(): Event;
}
