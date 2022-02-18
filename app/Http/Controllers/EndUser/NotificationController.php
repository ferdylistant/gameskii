<?php

namespace App\Http\Controllers\EndUser;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function markAsReadOneNotification(Request $request, $idNotification)
    {
        try {
            $user = User::where('id', auth('user')->user()->id)->first();
            $notif = $user->notifications()->where('id', $idNotification)->first();
            if ($notif) {
                $notif->markAsRead();
                return response()->json([
                    'Code' => 200,
                    'status' => 'success',
                    'message' => 'Notification has been marked as read',
                    'data' => $notif
                ],200);
            }
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getNotif()
    {
        try {
            $user = auth('user')->user()->unreadNotifications;
            foreach ($user as $notification) {
                // return response()->json($notification);
                $result[] = $notification;
            }
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function getCount()
    {
        try {
           $totalNotif = auth('user')->user()->unreadNotifications->groupBy('notifiable_type')->count();
           return response()->json([
               'code' => 200,
               'count' => $totalNotif
           ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => "error",
                'message' => $e->getMessage()
            ]);
        }
    }
}
