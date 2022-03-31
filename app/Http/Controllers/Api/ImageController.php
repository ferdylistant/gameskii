<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ImageController extends Controller
{
    public function getImage($imageName)
    {
        $imagePath = storage_path('uploads/avatar/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Image not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getPicture($imageName)
    {
        $imagePath = storage_path('uploads/picture-game/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Picture not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getPictureTeam($imageName)
    {
        $imagePath = storage_path('uploads/picture-team/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Picture not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getBannerTop($imageName)
    {
        $imagePath = storage_path('uploads/banner-game/top/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Banner not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getBannerBottom($imageName)
    {
        $imagePath = storage_path('uploads/banner-game/bottom/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Banner not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getPictureScrim($imageName)
    {
        $imagePath = storage_path('uploads/picture-scrim/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Pict not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getLogoRank($imageName)
    {
        $imagePath = storage_path('uploads/logo-rank/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Pict not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getPictureTournament($imageName)
    {
        $imagePath = storage_path('uploads/picture-tournament/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Pict not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getPictureSponsorTournament($imageName)
    {
        $imagePath = storage_path('uploads/sponsor-tournament/'.$imageName);
        if ((!file_exists($imagePath))) {
            return response()->json([
                "status" => "error",
                "message" => "Pict not found"
            ], 404);
        }
        try {
            $file = file_get_contents($imagePath);
            return response($file, 200)->header('Content-Type', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/bmp', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/webp', 'image/x-xbitmap', 'image/x-xpixmap', 'image/x-xwindowdump']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
