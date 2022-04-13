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
            ->orWhere('users.roles_id', '=', '2')
            ->select('users.*', 'roles.name as roles_name')
            ->get();
            foreach ($admin as $value) {
                $result[] = [
                    'id' => $value->id,
                    'name' => $value->name,
                    'email' => $value->email,
                    'phone' => $value->phone,
                    'fb' => $value->fb,
                    'ig' => $value->ig,
                    'provinsi' => $value->provinsi,
                    'kabupaten' => $value->kabupaten,
                    'kecamatan' => $value->kecamatan,
                    'tgl_lahir' => $value->tgl_lahir,
                    'avatar' => $value->avatar,
                    'roles_name' => $value->roles_name,
                    'is_verified' => $value->is_verified,
                    'email_verified_at' => $value->email_verified_at,
                    'ip_address' => $value->ip_address,
                    'last_login' => $value->last_login,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at
                ];
            }
            return response()->json([
                "status" => "success",
                "data" => $result
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
            if ($endUser->count() == 0) {
                return response()->json([
                    "status" => "error",
                    "message" => "No data"
                ], 404);
            }
            foreach ($endUser as $value) {
                $result[] = [
                    'id' => $value->id,
                    'name' => $value->name,
                    'email' => $value->email,
                    'phone' => $value->phone,
                    'fb' => $value->fb,
                    'ig' => $value->ig,
                    'provinsi' => $value->provinsi,
                    'kabupaten' => $value->kabupaten,
                    'kecamatan' => $value->kecamatan,
                    'tgl_lahir' => $value->tgl_lahir,
                    'avatar' => $value->avatar,
                    'roles_name' => $value->roles_name,
                    'is_verified' => $value->is_verified,
                    'email_verified_at' => $value->email_verified_at,
                    'ip_address' => $value->ip_address,
                    'last_login' => $value->last_login,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at
                ];
            }
            return response()->json([
                "status" => "success",
                "message" => "Get data success",
                "data" => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
