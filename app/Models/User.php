<?php

namespace App\Models;

use App\Support\Achievements;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'student_id', 'staff_id', 'faculty',
        'program', 'phone', 'avatar', 'is_active', 'lecturer_id', 'class_code',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function isLecturer(): bool
    {
        return $this->role === 'lecturer';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function forensicCases()
    {
        return $this->hasMany(ForensicCase::class, 'lecturer_id');
    }

    public function enrollments()
    {
        return $this->hasMany(CaseEnrollment::class, 'student_id');
    }

    /** The lecturer this student is assigned to. */
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /** Students assigned to this lecturer. */
    public function students()
    {
        return $this->hasMany(User::class, 'lecturer_id')->where('role', 'student');
    }

    /** Class-join requests this student has made (any status, most recent first is caller's job). */
    public function classJoinRequests()
    {
        return $this->hasMany(ClassJoinRequest::class, 'student_id');
    }

    /** Class-join requests students have made to this lecturer. */
    public function receivedJoinRequests()
    {
        return $this->hasMany(ClassJoinRequest::class, 'lecturer_id');
    }

    /**
     * Where a student stands on getting into a lecturer's class: already
     * approved (lecturer_id set), waiting on a pending request, declined
     * last time round, or never asked. Nothing here mutates state — it just
     * reads it — so it's safe to call from any view.
     */
    public function classStatus(): array
    {
        $pending = $this->classJoinRequests()->pending()->with('lecturer')->latest()->first();

        if ($this->lecturer_id) {
            // Already approved into a class. A pending row here means they've
            // since asked to switch to someone else — they keep their current
            // access until that switch is decided. If the most recent decided
            // request pointed at a *different* lecturer than the one they're
            // actually with, it was a switch attempt that got turned down —
            // surface that too, otherwise a rejected switch is invisible.
            $switchDecision = $this->classJoinRequests()
                ->where('status', 'rejected')
                ->where('lecturer_id', '!=', $this->lecturer_id)
                ->with('lecturer')
                ->latest('decided_at')
                ->first();

            return [
                'state' => 'approved',
                'lecturer' => $this->lecturer,
                'request' => null,
                'switch_request' => $pending,
                'switch_decision' => $switchDecision,
            ];
        }

        if ($pending) {
            return ['state' => 'pending', 'lecturer' => $pending->lecturer, 'request' => $pending, 'switch_request' => null, 'switch_decision' => null];
        }

        $latest = $this->classJoinRequests()->with('lecturer')->latest()->first();
        if ($latest && $latest->status === 'rejected') {
            return ['state' => 'rejected', 'lecturer' => $latest->lecturer, 'request' => $latest, 'switch_request' => null, 'switch_decision' => null];
        }

        return ['state' => 'none', 'lecturer' => null, 'request' => null, 'switch_request' => null, 'switch_decision' => null];
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasActiveCase(): bool
    {
        return $this->enrollments()
            ->whereIn('status', ['unlocked', 'in_progress'])
            ->exists();
    }

    public function currentEnrollment()
    {
        return $this->enrollments()
            ->whereIn('status', ['unlocked', 'in_progress'])
            ->with('forensicCase')
            ->latest()
            ->first();
    }

    /**
     * A game-style rank derived from completed cases and report marks —
     * computed on the fly from existing enrollment data, nothing stored.
     * Delegates to App\Support\Achievements so this always matches the
     * rank shown on the dashboard and record page.
     */
    public function investigatorRank(): array
    {
        return Achievements::rank(
            Achievements::xp(Achievements::completedEnrollments($this))
        );
    }

    /**
     * A short, human-friendly class code students use to join this lecturer's
     * cohort. Avoids visually ambiguous characters (0/O, 1/I/L).
     */
    public static function generateClassCode(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $code = collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (static::where('class_code', $code)->exists());

        return $code;
    }
}
