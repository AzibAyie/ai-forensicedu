<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'student_id', 'staff_id', 'faculty',
        'program', 'phone', 'avatar', 'is_active', 'lecturer_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function isLecturer(): bool { return $this->role === 'lecturer'; }
    public function isStudent(): bool { return $this->role === 'student'; }

    public function forensicCases() {
        return $this->hasMany(ForensicCase::class, 'lecturer_id');
    }

    public function enrollments() {
        return $this->hasMany(CaseEnrollment::class, 'student_id');
    }

    /** The lecturer this student is assigned to. */
    public function lecturer() {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /** Students assigned to this lecturer. */
    public function students() {
        return $this->hasMany(User::class, 'lecturer_id')->where('role', 'student');
    }

    public function activityLogs() {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasActiveCase(): bool {
        return $this->enrollments()
            ->whereIn('status', ['unlocked', 'in_progress'])
            ->exists();
    }

    public function currentEnrollment() {
        return $this->enrollments()
            ->whereIn('status', ['unlocked', 'in_progress'])
            ->with('forensicCase')
            ->latest()
            ->first();
    }

    /**
     * A game-style rank derived from completed cases and average score —
     * computed on the fly from existing enrollment data, nothing stored.
     */
    public function investigatorRank(): array {
        $completed = $this->enrollments()->whereIn('status', ['submitted', 'graded'])->count();

        $avgScore = $this->enrollments()
            ->whereHas('report', fn ($q) => $q->whereNotNull('marks'))
            ->with('report', 'forensicCase')
            ->get()
            ->avg(fn ($e) => $e->report->marks / max($e->forensicCase->total_marks, 1) * 100) ?? 0;

        $ranks = [
            ['min' => 0,  'score' => 0,  'label' => 'Recruit'],
            ['min' => 1,  'score' => 0,  'label' => 'Analyst'],
            ['min' => 3,  'score' => 60, 'label' => 'Investigator'],
            ['min' => 6,  'score' => 75, 'label' => 'Specialist'],
            ['min' => 10, 'score' => 85, 'label' => 'Chief Investigator'],
        ];

        $current = $ranks[0]['label'];
        foreach ($ranks as $r) {
            if ($completed >= $r['min'] && $avgScore >= $r['score']) {
                $current = $r['label'];
            }
        }

        return ['label' => $current, 'completed' => $completed, 'avg_score' => round($avgScore)];
    }
}
