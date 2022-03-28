<?php

namespace App\Events;

use App\Events\Event;

class JoinTournament extends Event
{
    public $tournamentMatch;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tournamentMatch)
    {
        $this->tournamentMatch = $tournamentMatch;
    }
}
