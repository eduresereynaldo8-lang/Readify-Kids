<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    protected $fillable = ['student_id', 'achievement_id', 'earned_at'];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function achievement() {
        return $this->belongsTo(Achievement::class);
    }
}