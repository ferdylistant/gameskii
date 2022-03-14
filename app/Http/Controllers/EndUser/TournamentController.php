<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Game;
use App\Models\User;
use App\Models\Tournament;
use App\Models\GameAccount;
use App\Models\EoTournament;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TournamentController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
        $this->game = new Game();
        $this->eo = new EoTournament();
        $this->tournament = new Tournament();
        $this->gameAccount = new GameAccount();
    }
    public function createTournament (Request $request)
    {
    }
}
