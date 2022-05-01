<?php

namespace App\Listeners;

use App\Events\ScrimMatchDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ScrimMatchDetailListener
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
     * @param  ScrimMatchDetail  $event
     * @return void
     */
    public function handle(ScrimMatchDetail $event)
    {
        $tables = $event->tables;
        $insert = DB::table('scrim_match_details')->insert($tables);
        return $insert;
    }
}
