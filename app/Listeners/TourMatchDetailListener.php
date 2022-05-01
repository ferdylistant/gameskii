<?php

namespace App\Listeners;

use App\Events\TourMatchDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TourMatchDetailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  TourMatchDetail  $event
     * @return void
     */
    public function handle(TourMatchDetail $event)
    {
        $tables = $event->tables;
        $insert = DB::table('tournament_match_details')->insert($tables);
        return $insert;
    }
}
