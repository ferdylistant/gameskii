<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
    }

    public function getAdmin()
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != "1" ) {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $admin = $this->user->join('roles', 'users.roles_id', '=', 'roles.id')
            ->where('users.roles_id', '=', '1')
            ->where('users.roles_id', '=', '2')
            ->select('users.*', 'roles.name as roles_name')
            ->get();
            return response()->json([
                "status" => "success",
                "data" => $admin
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function getEndUser()
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == "3" ) {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $endUser = $this->user->join('roles', 'users.roles_id', '=', 'roles.id')
            ->where('users.roles_id', '=', '3')
            ->select('users.*', 'roles.name as roles_name')
            ->get();
            return response()->json([
                "status" => "success",
                "data" => $endUser
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
