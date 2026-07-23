<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = ['achievement_name', 'description', 'criteria'];

    public function studentAchievements() {
        return $this->hasMany(StudentAchievement::class);
    }
}