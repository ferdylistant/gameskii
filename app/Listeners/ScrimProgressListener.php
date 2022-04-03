<?php

namespace App\Listeners;

use App\Events\ScrimProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ScrimProgressListener
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
     * @param  ScrimProgress  $event
     * @return void
     */
    public function handle(ScrimProgress $event)
    {
        $data = $event->data;
        $save = DB::table('scrim_progress')->insert($data);
        return $save;
    }
}
