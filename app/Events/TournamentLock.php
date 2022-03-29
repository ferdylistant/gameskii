<?php

namespace App\Events;

use App\Events\Event;

class TournamentLock extends Event
{
    public $tournament;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tournament)
    {
        $this->tournament = $tournament;
    }
}
