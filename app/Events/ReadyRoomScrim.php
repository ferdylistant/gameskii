<?php

namespace App\Events;

use App\Events\Event;

class ReadyRoomScrim extends Event
{
    public $matchScrim;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($matchScrim)
    {
        $this->matchScrim = $matchScrim;
    }
}
