<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassJoinRequest;

class ClassRequestController extends Controller
{
    public function index()
    {
        $lecturer = auth()->user();

        $pending = $lecturer->receivedJoinRequests()
            ->pending()
            ->with('student')
            ->latest()
            ->get();

        $decided = $lecturer->receivedJoinRequests()
            ->whereIn('status', ['approved', 'rejected'])
            ->with('student')
            ->latest('decided_at')
            ->limit(15)
            ->get();

        return view('lecturer.requests', compact('pending', 'decided'));
    }

    public function accept(ClassJoinRequest $joinRequest)
    {
        abort_unless($joinRequest->lecturer_id === auth()->id(), 403);

        if ($joinRequest->status !== 'pending') {
            return back()->withErrors(['request' => 'That request has already been decided.']);
        }

        $joinRequest->update(['status' => 'approved', 'decided_at' => now()]);
        $joinRequest->student->update(['lecturer_id' => $joinRequest->lecturer_id]);

        ActivityLog::record('CLASS_JOIN_APPROVED', "Approved {$joinRequest->student->name}'s request to join the class");
        ActivityLog::record('CLASS_JOIN_APPROVED', "Your request to join {$joinRequest->lecturer->name}'s class was approved", null, [], $joinRequest->student_id);

        return back()->with('success', "{$joinRequest->student->name} is now enrolled in your class.");
    }

    public function reject(ClassJoinRequest $joinRequest)
    {
        abort_unless($joinRequest->lecturer_id === auth()->id(), 403);

        if ($joinRequest->status !== 'pending') {
            return back()->withErrors(['request' => 'That request has already been decided.']);
        }

        $joinRequest->update(['status' => 'rejected', 'decided_at' => now()]);

        ActivityLog::record('CLASS_JOIN_REJECTED', "Declined {$joinRequest->student->name}'s request to join the class");
        ActivityLog::record('CLASS_JOIN_REJECTED', "Your request to join {$joinRequest->lecturer->name}'s class was declined", null, [], $joinRequest->student_id);

        return back()->with('success', "Request from {$joinRequest->student->name} declined.");
    }
}
