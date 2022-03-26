<?php

namespace App\Events;

class ReadyRoomTournament extends Event
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
