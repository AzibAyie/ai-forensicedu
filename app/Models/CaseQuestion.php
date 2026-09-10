<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseQuestion extends Model
{
    protected $fillable = ['forensic_case_id', 'question', 'marks', 'display_order'];

    public function forensicCase() {
        return $this->belongsTo(ForensicCase::class);
    }

    public function answers() {
        return $this->hasMany(CaseAnswer::class, 'question_id');
    }
}
