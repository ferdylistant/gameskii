<?php

namespace App\Http\Controllers;

use App\Models\Scrim;
use Illuminate\Http\Request;
use App\Models\ScrimProgress;
use App\Http\Controllers\Controller;

class ScrimProgressController extends Controller
{
    public function __construct()
    {
        $this->scrim = new Scrim();
        $this->scrimProgress = new ScrimProgress();
    }
    public function uploadResultMatch(Request $request, $idScrim){
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);

        }
        try{

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

}
