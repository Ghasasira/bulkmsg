<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Get message statistics grouped by type
        $messageStats = MessageLog::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->map(fn($item) => [
                'type' => $item->type,
                'count' => (string) $item->count
            ]);

        // Get counts of successful and failed recipient messages
        $successfulCount = 0;
        $failedCount = 0;

        MessageLog::each(function ($log) use (&$successfulCount, &$failedCount) {
            $successfulCount += $log->success_count;
            $failedCount += $log->failed_count;
        });

        // Get aggregated recipient stats by type and status
        $recipientStats = MessageLog::get()
            ->flatMap(function ($log) {
                return collect($log->details ?? [])->map(function ($detail) use ($log) {
                    return [
                        'type' => $log->type,
                        'status' => $detail['status'] ?? 'unknown'
                    ];
                });
            })
            ->groupBy(['type', 'status'])
            ->map(function ($typeGroup) {
                return $typeGroup->map(function ($statusGroup) {
                    return $statusGroup->count();
                });
            })
            ->flatMap(function ($typeData, $type) {
                return collect($typeData)->map(function ($count, $status) use ($type) {
                    return [
                        'type' => $type,
                        'status' => $status,
                        'count' => (string) $count
                    ];
                });
            })
            ->values();

        // Get recent messages with recipient count
        $recentMessages = MessageLog::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($message) => [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'created_at' => $message->created_at->toISOString(),
                'total_recipients' => $message->total_recipients,
                'success_count' => $message->success_count,
                'failed_count' => $message->failed_count,
                'cost' => $message->cost
            ]);

        // dd($recipientCounts);

        return Inertia::render('dashboard/index', [
            'analytics' => [
                'messageStats' => $messageStats,
                'recipientStats' => $recipientStats,
                'recentMessages' => $recentMessages,
                'recipientCounts' => [
                    'successful' => (string) $successfulCount,
                    'failed' => (string) $failedCount,
                    'total' => (string) ($successfulCount + $failedCount)
                ]
            ]
        ]);
    }
}
