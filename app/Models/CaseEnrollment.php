<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseEnrollment extends Model
{
    protected $fillable = [
        'forensic_case_id', 'student_id', 'status',
        'progress_percent', 'started_at', 'submitted_at',
        'accused_suspect', 'accusation_correct',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'accusation_correct' => 'boolean',
    ];

    public function forensicCase() {
        return $this->belongsTo(ForensicCase::class);
    }

    public function student() {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers() {
        return $this->hasMany(CaseAnswer::class, 'enrollment_id');
    }

    public function report() {
        return $this->hasOne(CaseReport::class, 'enrollment_id');
    }

    public function isActive(): bool {
        return in_array($this->status, ['unlocked', 'in_progress']);
    }

    public function isSubmitted(): bool {
        return in_array($this->status, ['submitted', 'graded']);
    }
}
