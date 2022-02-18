<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;

class GetDataController extends Controller
{
    public function getData(Request $request)
    {
        $data = auth('user')->user();
        try {
            $arrayData = [
                'code' => 200,
                'status' => 'success',
                'data' => $data
            ];
            return response()->json($arrayData, 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
