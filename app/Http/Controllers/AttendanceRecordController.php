<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, AttendanceEvent $attendanceEvent)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        
        // Check if registration is open
        $now = now();
        if ($now->lt($attendanceEvent->registration_start) || $now->gt($attendanceEvent->registration_end)) {
            return back()->withErrors(['error' => 'Registration is not currently open for this event.']);
        }
        
        // Check if user already checked in
        $existingRecord = AttendanceRecord::where('attendance_event_id', $attendanceEvent->id)
            ->where('cadre_id', auth()->id())
            ->exists();
            
        if ($existingRecord) {
            return back()->withErrors(['error' => 'You have already checked in to this event.']);
        }
        
        // Determine status based on timing
        $status = 'present';
        if ($now->gt($attendanceEvent->event_date)) {
            $status = 'late';
        }
        
        AttendanceRecord::create([
            'attendance_event_id' => $attendanceEvent->id,
            'cadre_id' => auth()->id(),
            'check_in_time' => $now,
            'status' => $status,
            'notes' => $request->notes,
            'check_in_method' => 'manual',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return redirect()->route('attendance.show', $attendanceEvent)
            ->with('success', 'Successfully checked in to the event.');
    }
}