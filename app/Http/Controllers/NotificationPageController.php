<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationPageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = Notification::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'student' => $user && $user->role === 'siswa'
                ? Student::where('user_id', $user->id)->first()
                : null,
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $user = Auth::user();
        $selectAllNotifications = $request->input('select_all_notifications') === 'true';
        \Log::info('[NotificationPageController.bulkDelete] called', [
            'user_id' => $user?->id,
            'select_all' => $selectAllNotifications,
            'input_keys' => array_keys($request->all()),
        ]);
        try {
            if ($selectAllNotifications) {
                // Delete ALL notifications for this user
                $deletedCount = Notification::where('user_id', $user->id)->delete();
            } else {
                // Delete specific selected notifications
                $validated = $request->validate([
                    'notification_ids' => ['required', 'array', 'min:1'],
                    'notification_ids.*' => ['integer'],
                ]);

                $deletedCount = Notification::where('user_id', $user->id)
                    ->whereIn('id', $validated['notification_ids'])
                    ->delete();
            }

            return redirect()->route('notifications.all')->with(
                'success',
                $deletedCount > 0
                    ? "{$deletedCount} notifikasi berhasil dihapus."
                    : 'Tidak ada notifikasi yang berhasil dihapus.'
            );
        } catch (\Throwable $e) {
            \Log::error('[NotificationPageController.bulkDelete] Error deleting notifications', [
                'select_all' => $selectAllNotifications,
                'notification_ids' => $request->input('notification_ids', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('notifications.all')->with('error', 'Gagal menghapus notifikasi: ' . $e->getMessage());
        }
    }
}
