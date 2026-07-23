<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = ['reward_name', 'description', 'points_required'];

    public function studentRewards() {
        return $this->hasMany(StudentReward::class);
    }
}