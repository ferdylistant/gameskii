<?php

namespace App\Events;

use App\Events\Event;

class ScrimLock extends Event
{
    public $scrimMaster;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($scrimMaster)
    {
        $this->scrimMaster = $scrimMaster;
    }
}
