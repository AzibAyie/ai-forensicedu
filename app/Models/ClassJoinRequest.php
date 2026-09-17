<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassJoinRequest extends Model
{
    protected $fillable = ['student_id', 'lecturer_id', 'status', 'decided_at'];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
