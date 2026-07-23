<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    protected $fillable = ['student_id', 'total_points'];

    public function student() {
        return $this->belongsTo(Student::class);
    }
}