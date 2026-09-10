<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'forensic_case_id', 'action',
        'description', 'ip_address', 'user_agent', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function forensicCase() {
        return $this->belongsTo(ForensicCase::class);
    }

    public static function record(string $action, string $description = '', ?int $caseId = null, array $metadata = [], ?int $actorId = null): void {
        $actorId = $actorId ?? (auth()->check() ? auth()->id() : null);
        if (!$actorId) return;
        static::create([
            'user_id' => $actorId,
            'forensic_case_id' => $caseId,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
