<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityResult extends Model
{
    protected $fillable = [
        'student_id', 'activity_id', 'score', 'mistakes',
        'time_spent', 'attempts', 'status', 'completed_at'
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}