<?php

namespace App\Events;

use App\Events\Event;

class AcceptReqTournament extends Event
{
    public $requestTeam;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($requestTeam)
    {
        $this->requestTeam = $requestTeam;
    }
}
