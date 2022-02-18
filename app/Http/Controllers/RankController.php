<?php

namespace App\Http\Controllers;

use App\Models\Rank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RankController extends Controller
{
    public function __construct()
    {
        $this->rank = new Rank();
    }
    public function create(Request $request)
    {
        $role = auth('user')->user()->roles_id;
        if ($role == '3'){
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'class' => 'required|string|max:30|unique:ranks,class',
            'min_rp' => 'required|integer|unique:ranks,min_rp',
            'max_rp' => 'required|integer|unique:ranks,max_rp',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 409);
        }
        try {
            $dataFile = $request->file('logo');
            $imageName = date('mdYHis') . $dataFile->hashName();
            $dataFile->move(storage_path('uploads/logo-ranks'), $imageName);
            $data = $request->all();
            $this->rank->class = $data['class'];
            $this->rank->min_rp = $data['min_rp'];
            $this->rank->max_rp = $data['max_rp'];
            $this->rank->logo = $imageName;
            if ($this->rank->save()) {
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'Rank created successfully!'
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
