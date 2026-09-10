<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ForensicCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecturer_id', 'title', 'incident_type', 'difficulty',
        'description', 'scenario', 'learning_objectives',
        'investigation_instructions', 'simulated_evidence', 'timeline_events',
        'question_pdf_path', 'question_pdf_name', 'publish_at', 'close_at',
        'password', 'is_locked', 'is_published',
        'expected_duration', 'total_marks', 'ai_generated',
    ];

    protected $casts = [
        'simulated_evidence' => 'array',
        'timeline_events' => 'array',
        'publish_at' => 'datetime',
        'close_at' => 'datetime',
        'is_locked' => 'boolean',
        'is_published' => 'boolean',
        'ai_generated' => 'boolean',
    ];

    protected $hidden = ['password'];

    public function lecturer() {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function questions() {
        return $this->hasMany(CaseQuestion::class)->orderBy('display_order');
    }

    public function enrollments() {
        return $this->hasMany(CaseEnrollment::class);
    }

    public function activityLogs() {
        return $this->hasMany(ActivityLog::class);
    }

    public function getDifficultyBadgeAttribute(): string {
        return match($this->difficulty) {
            'beginner' => 'bg-green-100 text-green-800',
            'intermediate' => 'bg-yellow-100 text-yellow-800',
            'advanced' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getIncidentLabelAttribute(): string {
        return match($this->incident_type) {
            'unauthorized_modification' => 'Unauthorized Data Modification',
            'brute_force' => 'Brute Force Login Attack',
            'mass_deletion' => 'Mass Data Deletion',
            default => 'Unknown',
        };
    }

    public function getIncidentIconAttribute(): string {
        return match($this->incident_type) {
            'unauthorized_modification' => '✏️',
            'brute_force' => '🔓',
            'mass_deletion' => '🗑️',
            default => '❓',
        };
    }

    /** A case is only reachable when published AND inside its scheduling window. */
    public function isAvailable(): bool {
        if (! $this->is_published) return false;
        if ($this->publish_at && $this->publish_at->isFuture()) return false;
        if ($this->close_at && $this->close_at->isPast()) return false;
        return true;
    }

    public function availabilityLabel(): string {
        if (! $this->is_published) return 'Draft';
        if ($this->publish_at && $this->publish_at->isFuture()) return 'Opens '.$this->publish_at->format('d M, H:i');
        if ($this->close_at && $this->close_at->isPast()) return 'Closed';
        if ($this->close_at) return 'Closes '.$this->close_at->format('d M, H:i');
        return 'Open';
    }

    public function scopeAvailable($q) {
        return $q->where('is_published', true)
            ->where(fn($x) => $x->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn($x) => $x->whereNull('close_at')->orWhere('close_at', '>=', now()));
    }

    public function checkPassword(string $input): bool {
        return $this->password && $input === $this->password;
    }

    public function submittedCount(): int {
        return $this->enrollments()->where('status', 'submitted')->orWhere('status', 'graded')->count();
    }

    public function enrolledCount(): int {
        return $this->enrollments()->count();
    }
}
