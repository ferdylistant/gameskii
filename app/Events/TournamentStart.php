<?php

namespace App\Events;

use App\Events\Event;

class TournamentStart extends Event
{
    public $tournamentLock;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tournamentLock)
    {
        $this->tournamentLock = $tournamentLock;
    }
}
