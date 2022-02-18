<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EmailVerificationController extends Controller
{
    public function __construct()
    {
        $this->endUser = new User();
    }
    public function emailVerify(Request $request)
    {
        $email = $request->get('email');
        $token = $request->get('token');
        $data = $this->endUser->where([
            ['email','=', $email],
            ['remember_token','=', $token]
        ])->first();
        if (!$data) {
            return response()->json([
                'code' => 404,
                'status' => 'failed',
                'message' => 'Invalid get data'
            ], 404);
        }

        $dateVerified = new Carbon($data->email_verified_at);
        $now =  Carbon::now('Asia/Jakarta')->toDateTimeString();
        $difHour = $dateVerified->diffInHours($now);

        if ($difHour > '1') {
            $data->delete('remember_token');
            return response()->json([
                "code" => 410,
                "status" => "expired",
                "message" => "Email verification failed, token has expired"
            ], 410);
        }
        try {
            $data->forceFill([
                'is_verified' => '1',
                'email_verified_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                'ip_address' => $request->getClientIp()
                ])->save();
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Your account is active'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
