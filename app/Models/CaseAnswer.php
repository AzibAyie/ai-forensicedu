<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseAnswer extends Model
{
    protected $fillable = ['enrollment_id', 'question_id', 'answer', 'flagged_external_paste'];

    protected $casts = ['flagged_external_paste' => 'boolean'];

    public function enrollment() {
        return $this->belongsTo(CaseEnrollment::class, 'enrollment_id');
    }

    public function question() {
        return $this->belongsTo(CaseQuestion::class, 'question_id');
    }
}
