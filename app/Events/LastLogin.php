<?php

namespace App\Events;

use App\Events\Event;
class LastLogin extends Event
{
    public $data;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data;
    }
}
