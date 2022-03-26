<?php

namespace App\Providers;

use App\Events\LastLogin;
use App\Events\ReadyRoomTournament;
use App\Listeners\LastLoginListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\ServiceProvider;
use App\Listeners\ReadyRoomTournamentListener;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        LastLogin::class => [
            LastLoginListener::class,
        ],
        ReadyRoomTournament::class => [
            ReadyRoomTournamentListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
