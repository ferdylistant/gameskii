<?php

namespace App\Events;

use App\Events\Event;

class AcceptReqScrim extends Event
{
    public $scrimMatch;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($scrimMatch)
    {
        $this->scrimMatch = $scrimMatch;
    }
}
