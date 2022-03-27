<?php

namespace App\Providers;

use App\Events\JoinScrim;
use App\Events\LastLogin;
use App\Events\ScrimLock;
use App\Events\ScrimUnlock;
use App\Events\AcceptReqScrim;
use App\Events\ReadyRoomScrim;
use App\Events\RejectReqScrim;
use App\Events\NotReadyRoomScrim;
use App\Events\ReadyRoomTournament;
use App\Listeners\JoinScrimListener;
use App\Listeners\LastLoginListener;
use App\Listeners\ScrimLockListener;
use Illuminate\Support\Facades\Event;
use App\Listeners\ScrimUnlockListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\ServiceProvider;
use App\Listeners\AcceptReqScrimListener;
use App\Listeners\ReadyRoomScrimListener;
use App\Listeners\RejectReqScrimListener;
use App\Listeners\NotReadyRoomScrimListener;
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
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {

        Event::listen(
            ReadyRoomScrim::class,
            [ReadyRoomScrimListener::class, 'handle'],
        );
        Event::listen(
            LastLogin::class,
            [LastLoginListener::class, 'handle'],
        );
        Event::listen(
            ReadyRoomTournament::class,
            [ReadyRoomTournamentListener::class, 'handle'],
        );
        Event::listen(
            NotReadyRoomScrim::class,
            [NotReadyRoomScrimListener::class, 'handle'],
        );
        Event::listen(
            ScrimLock::class,
            [ScrimLockListener::class, 'handle'],
        );
        Event::listen(
            ScrimUnlock::class,
            [ScrimUnlockListener::class, 'handle'],
        );
        Event::listen(
            JoinScrim::class,
            [JoinScrimListener::class, 'handle'],
        );
        Event::listen(
            AcceptReqScrim::class,
            [AcceptReqScrimListener::class, 'handle'],
        );
        Event::listen(
            RejectReqScrim::class,
            [RejectReqScrimListener::class, 'handle'],
        );
    }
}
