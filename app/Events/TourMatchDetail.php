<?php

namespace App\Events;

use App\Events\Event;

class TourMatchDetail extends Event
{
    public $tables;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tables)
    {
        $this->tables = $tables;
    }
}
