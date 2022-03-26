<?php

namespace App\Events;

use App\Events\Event;
class LastLogin extends Event
{
    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
