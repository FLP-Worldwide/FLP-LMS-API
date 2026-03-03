<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class BatchAnnouncementController extends Controller
{
    /* =========================================================
       GET  /batches/{id}/announcements
    ========================================================= */
   public function index($batchId)
    {
        $now = now();

        $announcements = Announcement::where('batch_id', $batchId)
            ->latest()
            ->get()
            ->map(function ($announcement) use ($now) {

                // 🔥 Dynamic Status Logic
                if ($announcement->status === 'DRAFT') {
                    $displayStatus = 'DRAFT';
                }
                elseif ($announcement->schedule_for_later) {

                    if ($announcement->scheduled_at && $announcement->scheduled_at > $now) {
                        $displayStatus = 'SCHEDULED';
                    } else {
                        $displayStatus = 'LIVE';
                    }

                } else {
                    $displayStatus = 'PUBLISHED';
                }

                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'category' => $announcement->category,
                    'description' => $announcement->description,
                    'attachment_url' => $announcement->attachment
                        ? asset('storage/'.$announcement->attachment)
                        : null,
                    'original_status' => $announcement->status,
                    'display_status' => $displayStatus,
                    'schedule_for_later' => $announcement->schedule_for_later,
                    'scheduled_at' => $announcement->scheduled_at,
                    'created_at' => $announcement->created_at,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }

    /* =========================================================
       POST  /batches/{id}/announcements
    ========================================================= */
    public function store(Request $request, $batchId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:DRAFT,PUBLISHED',
            'schedule_for_later' => 'boolean',
            'scheduled_at' => 'nullable|date',
            'attachment' => 'nullable|file|max:5120'
        ]);

        $batch = Batch::findOrFail($batchId);

        $filePath = null;
        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')
                ->store('announcements', 'public');
        }

        $announcement = Announcement::create([
            'batch_id' => $batch->id,
            'title' => $request->title,
            'category' => $request->category,
            'description' => $request->description,
            'status' => $request->status,
            'schedule_for_later' => $request->schedule_for_later ?? false,
            'scheduled_at' => $request->scheduled_at,
            'attachment' => $filePath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement created successfully',
            'data' => $announcement
        ]);
    }

    /* =========================================================
       PUT  /batches/{id}/announcements/{announcementId}
    ========================================================= */
    public function update(Request $request, $batchId, $announcementId)
    {
        $announcement = Announcement::where('batch_id', $batchId)
            ->findOrFail($announcementId);

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:DRAFT,PUBLISHED',
            'schedule_for_later' => 'boolean',
            'scheduled_at' => 'nullable|date',
            'attachment' => 'nullable|file|max:5120'
        ]);

        if ($request->hasFile('attachment')) {

            if ($announcement->attachment) {
                Storage::disk('public')->delete($announcement->attachment);
            }

            $filePath = $request->file('attachment')
                ->store('announcements', 'public');

            $announcement->attachment = $filePath;
        }

        $announcement->update([
            'title' => $request->title,
            'category' => $request->category,
            'description' => $request->description,
            'status' => $request->status,
            'schedule_for_later' => $request->schedule_for_later ?? false,
            'scheduled_at' => $request->scheduled_at,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement updated successfully',
            'data' => $announcement
        ]);
    }

    /* =========================================================
       DELETE  /batches/{id}/announcements/{announcementId}
    ========================================================= */
    public function destroy($batchId, $announcementId)
    {
        $announcement = Announcement::where('batch_id', $batchId)
            ->findOrFail($announcementId);

        if ($announcement->attachment) {
            Storage::disk('public')->delete($announcement->attachment);
        }

        $announcement->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement deleted successfully'
        ]);
    }
}
