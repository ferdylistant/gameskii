<?php

namespace App\Events;

use App\Events\Event;

class ScrimProgress extends Event
{
    public $data;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
}
