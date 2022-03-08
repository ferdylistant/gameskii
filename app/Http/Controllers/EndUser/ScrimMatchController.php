<?php

namespace App\Http\Controllers\EndUser;

use Ramsey\Uuid\Uuid;
use App\Models\Scrim;
use App\Models\ScrimMatch;
use Illuminate\Http\Request;
use App\Models\ScrimProgress;
use App\Http\Controllers\Controller;

class ScrimMatchController extends Controller
{
    public function __construct()
    {
        $this->scrim = new Scrim();
        $this->scrimMatch = new ScrimMatch();
        $this->scrimProgress = new ScrimProgress();
    }
}
