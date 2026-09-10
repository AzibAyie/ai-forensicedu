<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseReport extends Model
{
    protected $fillable = [
        'enrollment_id', 'student_name', 'student_id_number', 'program',
        'executive_summary', 'findings', 'timeline_reconstruction',
        'recommendations', 'conclusion', 'marks', 'lecturer_feedback',
        'ai_feedback', 'ai_suggested_marks', 'status',
        'answer_pdf_path', 'answer_pdf_name',
        'keystroke_count', 'paste_count', 'pasted_chars', 'compose_seconds',
        'revision_count', 'paste_events', 'integrity_flags',
        'similarity_score', 'similar_to_report_id',
    ];

    protected $casts = [
        'paste_events' => 'array',
        'integrity_flags' => 'array',
    ];

    public function bodyText(): string {
        return implode(' ', array_filter([
            $this->executive_summary, $this->findings,
            $this->timeline_reconstruction, $this->recommendations, $this->conclusion,
        ]));
    }

    public function enrollment() {
        return $this->belongsTo(CaseEnrollment::class, 'enrollment_id');
    }
}
