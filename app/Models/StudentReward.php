<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StudentReward extends Model
{
    protected $fillable = ['student_id', 'reward_id', 'claimed_at'];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function reward() {
        return $this->belongsTo(Reward::class);
    }
}