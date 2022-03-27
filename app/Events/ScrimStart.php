<?php

namespace App\Events;

use App\Events\Event;

class ScrimStart extends Event
{
    public $scrimLock;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($scrimLock)
    {
        $this->scrimLock = $scrimLock;
    }
}
