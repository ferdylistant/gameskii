<?php

namespace App\Events;

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
