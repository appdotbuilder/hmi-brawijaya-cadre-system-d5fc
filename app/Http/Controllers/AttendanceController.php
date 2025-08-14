<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceEventRequest;
use App\Http\Requests\UpdateAttendanceEventRequest;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = AttendanceEvent::with(['creator'])
            ->active()
            ->latest()
            ->paginate(15);
        
        return Inertia::render('attendance/index', [
            'events' => $events
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('attendance/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttendanceEventRequest $request)
    {
        $event = AttendanceEvent::create(array_merge(
            $request->validated(),
            [
                'created_by' => auth()->id(),
                'qr_code' => 'QR-' . strtoupper(uniqid())
            ]
        ));

        return redirect()->route('attendance.show', $event)
            ->with('success', 'Attendance event created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AttendanceEvent $attendance)
    {
        $attendance->load(['creator', 'attendanceRecords.cadre']);
        
        // Check if current user has already checked in
        $userAttendance = null;
        if (auth()->check()) {
            $userAttendance = AttendanceRecord::where('attendance_event_id', $attendance->id)
                ->where('cadre_id', auth()->id())
                ->first();
        }
        
        return Inertia::render('attendance/show', [
            'event' => $attendance,
            'userAttendance' => $userAttendance
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AttendanceEvent $attendance)
    {
        return Inertia::render('attendance/edit', [
            'event' => $attendance
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttendanceEventRequest $request, AttendanceEvent $attendance)
    {
        $attendance->update($request->validated());

        return redirect()->route('attendance.show', $attendance)
            ->with('success', 'Attendance event updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttendanceEvent $attendance)
    {
        $attendance->delete();

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance event deleted successfully.');
    }


}